<?php
// app/Http/Controllers/Api/ProductsExportController.php (Updated)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ExportLog;
use App\Exports\ProductsExport;
use App\Jobs\ExportProductsJob;
use App\Http\Resources\ProductResource;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductsExportController extends Controller
{
    // Existing buildQuery method remains the same...
    private function buildQuery(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('sku', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('quantity')) {
            $query->where('quantity', '<=', $request->quantity);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['id', 'name', 'price', 'quantity', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }

    // NEW: Export with format parameter
    public function export(Request $request, $format)
    {
        $validFormats = ['json', 'csv', 'excel', 'pdf', 'zip'];
        
        if (!in_array($format, $validFormats)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid format. Allowed: ' . implode(', ', $validFormats)
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($format === 'zip') {
            return $this->exportZip($request);
        }

        // For large datasets, use queue
        if ($this->buildQuery($request)->count() > 1000) {
            return $this->queueExport($request, $format);
        }

        // Small datasets - direct download
        return $this->directExport($request, $format);
    }

    // NEW: Queue export for large datasets
    public function queueExport(Request $request, $format)
    {
        $filters = $request->all();
        $email = $request->get('email');
        
        $exportLog = ExportLog::create([
            'export_type' => 'products',
            'format' => $format,
            'filters' => $filters,
            'status' => 'pending',
            'user_email' => $email,
            'filename' => ''
        ]);

        ExportProductsJob::dispatch($filters, $format, $exportLog->id, $email);

        return response()->json([
            'status' => true,
            'message' => 'Export started in background. You will be notified when ready.',
            'export_id' => $exportLog->id,
            'status_url' => route('api.export.status', $exportLog->id)
        ]);
    }

    // NEW: Check export status
    public function exportStatus($id)
    {
        $exportLog = ExportLog::findOrFail($id);

        return response()->json([
            'status' => $exportLog->status,
            'records_count' => $exportLog->records_count,
            'download_url' => $exportLog->status === 'completed' ? url($exportLog->download_url) : null,
            'created_at' => $exportLog->created_at,
            'completed_at' => $exportLog->completed_at
        ]);
    }

    // NEW: Download export file
    public function downloadExport($id)
    {
        $exportLog = ExportLog::findOrFail($id);
        
        if ($exportLog->status !== 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'Export not ready yet. Current status: ' . $exportLog->status
            ], Response::HTTP_ACCEPTED);
        }

        if (!Storage::disk('public')->exists($exportLog->filename)) {
            return response()->json([
                'status' => false,
                'message' => 'File not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('public')->download($exportLog->filename);
    }

    // NEW: Export as ZIP (multiple formats)
    public function exportZip(Request $request)
    {
        $formats = $request->get('formats', ['csv', 'excel', 'pdf']);
        $zipFilename = 'exports/products_' . time() . '.zip';
        $zipPath = storage_path('app/public/' . $zipFilename);
        
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        foreach ($formats as $format) {
            $tempFile = $this->generateTempExport($request, $format);
            $zip->addFile($tempFile, "products_export.{$format}");
        }
        
        $zip->close();
        
        // Cleanup temp files
        foreach ($formats as $format) {
            $tempFile = storage_path("app/temp/products_export.{$format}");
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    // NEW: Get export history
    public function exportHistory(Request $request)
    {
        $history = ExportLog::orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return response()->json([
            'status' => true,
            'data' => $history
        ]);
    }

    // NEW: Scheduled export setup
    public function scheduleExport(Request $request)
    {
        $request->validate([
            'format' => 'required|in:json,csv,excel,pdf',
            'frequency' => 'required|in:daily,weekly,monthly',
            'email' => 'required|email',
            'filters' => 'array'
        ]);

        // Store schedule in database (create schedules table)
        $schedule = \App\Models\ExportSchedule::create([
            'format' => $request->format,
            'frequency' => $request->frequency,
            'filters' => $request->filters,
            'email' => $request->email,
            'last_run' => null,
            'next_run' => $this->calculateNextRun($request->frequency),
            'is_active' => true
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Schedule created successfully',
            'schedule' => $schedule
        ]);
    }

    private function calculateNextRun($frequency)
    {
        return match($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay()
        };
    }

    private function generateTempExport(Request $request, $format)
    {
        $tempPath = storage_path("app/temp/products_export.{$format}");
        $query = $this->buildQuery($request);
        
        switch ($format) {
            case 'csv':
                $this->saveCsvTemp($query, $tempPath);
                break;
            case 'excel':
                Excel::store(new ProductsExport($request->all()), "temp/products_export.{$format}", 'local');
                $tempPath = storage_path("app/temp/products_export.{$format}");
                break;
            case 'pdf':
                $products = $query->get();
                $pdf = Pdf::loadView('pdf.products', compact('products'));
                file_put_contents($tempPath, $pdf->output());
                break;
            case 'json':
                file_put_contents($tempPath, $query->get()->toJson(JSON_PRETTY_PRINT));
                break;
        }
        
        return $tempPath;
    }

    private function saveCsvTemp($query, $path)
    {
        $file = fopen($path, 'w');
        fputcsv($file, ['ID', 'Name', 'SKU', 'Price', 'Quantity', 'Created At']);
        
        $query->chunk(500, function($products) use ($file) {
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id, $product->name, $product->sku,
                    $product->price, $product->quantity, $product->created_at
                ]);
            }
        });
        
        fclose($file);
    }

    private function directExport(Request $request, $format)
    {
        return match($format) {
            'json' => $this->exportJson($request),
            'csv' => $this->exportCsv($request),
            'excel' => $this->exportExcel($request),
            'pdf' => $this->exportPdf($request),
            default => $this->exportJson($request)
        };
    }

    // Your existing export methods remain...
    public function exportJson(Request $request)
    {
        $products = $this->buildQuery($request)->paginate(50);
        return ProductResource::collection($products)->additional([
            'status' => true,
            'message' => 'Products fetched successfully'
        ]);
    }

    public function exportCsv(Request $request)
    {
        $filename = "products_export.csv";
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($request) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'SKU', 'Price', 'Quantity', 'Created At']);
            
            $this->buildQuery($request)->chunk(500, function ($products) use ($file) {
                foreach ($products as $product) {
                    fputcsv($file, [
                        $product->id, $product->name, $product->sku,
                        $product->price, $product->quantity, $product->created_at
                    ]);
                }
            });
            fclose($file);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new ProductsExport($request->all()), 'products_' . time() . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $products = $this->buildQuery($request)->limit(1000)->get();
        $pdf = Pdf::loadView('pdf.products', compact('products'));
        return $pdf->download('products_filtered.pdf');
    }
}