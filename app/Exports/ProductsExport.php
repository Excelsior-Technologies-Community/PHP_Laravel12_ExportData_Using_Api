<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Product::query();

        if ($this->request->min_price) {
            $query->where('price', '>=', $this->request->min_price);
        }

        if ($this->request->max_price) {
            $query->where('price', '<=', $this->request->max_price);
        }

        if ($this->request->quantity) {
            $query->where('quantity', '<=', $this->request->quantity);
        }

        return $query->select(
            'id',
            'name',
            'sku',
            'price',
            'quantity',
            'created_at'
        )->get();
    }

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