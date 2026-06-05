<?php
// app/Jobs/ExportProductsJob.php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ExportLog;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExportReadyMail;

class ExportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $filters;
    protected $format;
    protected $exportLogId;
    protected $email;

    public function __construct($filters, $format, $exportLogId, $email = null)
    {
        $this->filters = $filters;
        $this->format = $format;
        $this->exportLogId = $exportLogId;
        $this->email = $email;
    }

    public function handle()
    {
        $exportLog = ExportLog::find($this->exportLogId);
        $exportLog->update(['status' => 'processing']);

        try {
            $query = $this->buildQuery();
            $filename = "exports/products_" . time() . ".{$this->format}";
            
            switch ($this->format) {
                case 'csv':
                    $this->exportCsv($query, $filename);
                    break;
                case 'excel':
                    $this->exportExcel($query, $filename);
                    break;
                case 'pdf':
                    $this->exportPdf($query, $filename);
                    break;
                case 'json':
                    $this->exportJson($query, $filename);
                    break;
            }

            $exportLog->update([
                'status' => 'completed',
                'filename' => $filename,
                'download_url' => Storage::url($filename),
                'records_count' => $query->count(),
                'completed_at' => now()
            ]);

            // Send email if provided
            if ($this->email) {
                Mail::to($this->email)->send(new ExportReadyMail($exportLog));
            }

        } catch (\Exception $e) {
            $exportLog->update([
                'status' => 'failed',
                'completed_at' => now()
            ]);
            throw $e;
        }
    }

    private function buildQuery()
    {
        $query = Product::query();

        if (!empty($this->filters['search'])) {
            $query->where(function($q) {
                $q->where('name', 'LIKE', '%' . $this->filters['search'] . '%')
                  ->orWhere('sku', 'LIKE', '%' . $this->filters['search'] . '%');
            });
        }

        if (!empty($this->filters['min_price'])) {
            $query->where('price', '>=', $this->filters['min_price']);
        }

        if (!empty($this->filters['max_price'])) {
            $query->where('price', '<=', $this->filters['max_price']);
        }

        return $query;
    }

    private function exportCsv($query, $filename)
    {
        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
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
        };

        $content = $this->captureOutput($callback);
        Storage::put($filename, $content);
    }

    private function exportExcel($query, $filename)
    {
        Excel::store(new ProductsExport($this->filters), $filename, 'public');
    }

    private function exportPdf($query, $filename)
    {
        $products = $query->get();
        $pdf = Pdf::loadView('pdf.products', compact('products'));
        Storage::put($filename, $pdf->output());
    }

    private function exportJson($query, $filename)
    {
        $products = $query->get();
        Storage::put($filename, json_encode($products, JSON_PRETTY_PRINT));
    }

    private function captureOutput($callback)
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }
}