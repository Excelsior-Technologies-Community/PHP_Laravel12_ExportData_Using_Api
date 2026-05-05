<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Exports\ProductsExport;
use App\Http\Resources\ProductResource;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductsExportController extends Controller
{
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
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->price,
                        $product->quantity,
                        $product->created_at
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }

    public function exportExcel(Request $request)
    {
        $fileName = 'exports/products_' . time() . '.xlsx';

        Excel::queue(new ProductsExport($request->all()), $fileName, 'public');

        return response()->json([
            'status' => true,
            'message' => 'Excel export started in background.',
            'download_url' => asset("storage/" . $fileName)
        ]);
    }

    public function exportPdf(Request $request)
    {
        $products = $this->buildQuery($request)->limit(1000)->get();

        $pdf = Pdf::loadView('pdf.products', compact('products'));

        return $pdf->download('products_filtered.pdf');
    }
}