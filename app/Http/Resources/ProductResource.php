<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->id,
            'details' => [
                'name' => $this->name,
                'sku' => $this->sku,
            ],
            'pricing' => [
                'price' => (float) $this->price,
                'stock_available' => (int) $this->quantity,
            ],
            'created_at' => $this->created_at->format('d-M-Y H:i A'),
        ];
    }
}