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
        Product::create([
            'name' => 'iPhone 15',
            'sku' => 'SKU001',
            'price' => 799.99,
            'quantity' => 10
        ]);

        Product::create([
            'name' => 'Samsung S23',
            'sku' => 'SKU002',
            'price' => 699.50,
            'quantity' => 5
        ]);

        Product::create([
            'name' => 'OnePlus 12',
            'sku' => 'SKU003',
            'price' => 599.00,
            'quantity' => 20
        ]);
    }
}
