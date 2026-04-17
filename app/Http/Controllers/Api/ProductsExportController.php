<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductsExportController extends Controller
{
    //  JSON Export
    public function exportJson(Request $request)
    {
        $query = Product::query();

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->quantity) {
            $query->where('quantity', '<=', $request->quantity);
        }

        $products = $query->get();

        return response()->json([
            'status' => true,
            'data'   => $products
        ]);
    }

    //  CSV Export
    public function exportCsv(Request $request)
    {
        $query = Product::query();

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->quantity) {
            $query->where('quantity', '<=', $request->quantity);
        }

        $products = $query->get();

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

    //  Excel Export
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new ProductsExport($request),
            'products_export.xlsx'
        );
    }

    //  PDF Export
    public function exportPdf(Request $request)
    {
        $query = Product::query();

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->quantity) {
            $query->where('quantity', '<=', $request->quantity);
        }

        $products = $query->get();

        $pdf = Pdf::loadView('pdf.products', compact('products'));

        return $pdf->download('products.pdf');
    }
}