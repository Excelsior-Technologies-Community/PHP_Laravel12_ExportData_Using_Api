<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductsExportController extends Controller
{
    // ✅ Export Products as JSON
    public function exportJson()
    {
        $products = Product::all();

        return response()->json([
            'status' => true,
            'data'   => $products
        ], Response::HTTP_OK);
    }

    // ✅ Export Products as CSV
    public function exportCsv()
    {
        $products = Product::all();
        $filename = "products_export.csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID','Name','SKU','Price','Quantity','Created At']);

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

            fclose($file);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }

    // ✅ Export Products as Excel
    public function exportExcel()
    {
        return Excel::download(
            new ProductsExport,
            'products_export.xlsx'
        );
    }
}
