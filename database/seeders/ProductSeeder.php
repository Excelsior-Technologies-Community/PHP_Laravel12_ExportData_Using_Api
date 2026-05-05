<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $products = [
            [
                'name' => 'iPhone 15',
                'sku' => 'SKU001',
                'price' => 799.99,
                'quantity' => 10
            ],
            [
                'name' => 'Samsung S23',
                'sku' => 'SKU002',
                'price' => 699.50,
                'quantity' => 5
            ],
            [
                'name' => 'OnePlus 12',
                'sku' => 'SKU003',
                'price' => 599.00,
                'quantity' => 20
            ],
            [
                'name' => 'MacBook Pro M5',
                'sku' => 'SKU004',
                'price' => 1999.00,
                'quantity' => 8
            ],
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'sku' => 'SKU005',
                'price' => 348.00,
                'quantity' => 15
            ],
            [
                'name' => 'iPad Air 5th Gen',
                'sku' => 'SKU006',
                'price' => 599.00,
                'quantity' => 25
            ],
            [
                'name' => 'Dell XPS 15',
                'sku' => 'SKU007',
                'price' => 1899.99,
                'quantity' => 6
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'sku' => 'SKU008',
                'price' => 999.00,
                'quantity' => 12
            ],
            [
                'name' => 'Apple Watch Series 9',
                'sku' => 'SKU009',
                'price' => 399.00,
                'quantity' => 30
            ],
            [
                'name' => 'LG C3 OLED 55-inch TV',
                'sku' => 'SKU010',
                'price' => 1299.99,
                'quantity' => 4
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}