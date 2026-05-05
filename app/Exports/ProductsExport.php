<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProductsExport implements FromQuery, WithHeadings, ShouldQueue
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Product::query();

        if (isset($this->filters['search']) && !empty($this->filters['search'])) {
            $query->where(function($q) {
                $q->where('name', 'LIKE', '%' . $this->filters['search'] . '%')
                  ->orWhere('sku', 'LIKE', '%' . $this->filters['search'] . '%');
            });
        }

        if (isset($this->filters['min_price']) && $this->filters['min_price'] !== null) {
            $query->where('price', '>=', $this->filters['min_price']);
        }

        if (isset($this->filters['max_price']) && $this->filters['max_price'] !== null) {
            $query->where('price', '<=', $this->filters['max_price']);
        }

        if (isset($this->filters['quantity']) && $this->filters['quantity'] !== null) {
            $query->where('quantity', '<=', $this->filters['quantity']);
        }

        if (isset($this->filters['start_date']) && !empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (isset($this->filters['end_date']) && !empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'id';
        $sortDir = $this->filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['id', 'name', 'price', 'quantity', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->select(
            'id',
            'name',
            'sku',
            'price',
            'quantity',
            'created_at'
        );
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