<?php
// app/Exports/ProductsExport.php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;
    
    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }
    
    public function collection()
    {
        $query = Product::query();
        
        if (!empty($this->filters['search'])) {
            $query->where('name', 'LIKE', '%' . $this->filters['search'] . '%');
        }
        
        if (!empty($this->filters['min_price'])) {
            $query->where('price', '>=', $this->filters['min_price']);
        }
        
        return $query->get();
    }
    
    public function headings(): array
    {
        return ['ID', 'Name', 'SKU', 'Price', 'Quantity', 'Created At'];
    }
    
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->price,
            $product->quantity,
            $product->created_at->format('Y-m-d H:i:s')
        ];
    }
}