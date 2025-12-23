<?php

namespace App\Exports;

use App\Models\Product;   // ✅ CORRECT
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    // Return products data for Excel
    public function collection()
    {
        return Product::select(
            'id',
            'name',
            'sku',
            'price',
            'quantity',
            'created_at'
        )->get();
    }

    // Column headings
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'SKU',
            'Price',
            'Quantity',
            'Created At'
        ];
    }
}
