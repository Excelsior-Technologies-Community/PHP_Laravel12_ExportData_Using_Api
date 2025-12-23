# PHP_Laravel12_ExportData_Using_Api



We will export users data as:

JSON (default API)

CSV

Excel (XLSX)

using API routes.

---


## Project Overview (Easy Words)

We will:

Create Laravel 12 project

Create database table (products)

Insert sample data (manually)

Create API controller

Create API routes

Export data as:

JSON

CSV

Excel


---


## Technologies Used

PHP 8+

Laravel 12

MySQL

API Routes

maatwebsite/excel (for Excel export)


---



## STEP 1: Create Laravel 12 Project

Command:

```
composer create-project laravel/laravel PHP_Laravel12_ExportData_Using_Api "12.*"
```

Go inside project:
```
cd PHP_Laravel12_ExportData_Using_Api
```

Run server:
```
php artisan serve
```

Explanation:

Creates a fresh Laravel 12 project
This sets up a fresh Laravel 12 project.





## STEP 2: Configure Database

Open .env
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=export_api_db
DB_USERNAME=root
DB_PASSWORD=   # your password
```

Create database in phpMyAdmin:
```
export_api_db
```

Explanation:

Connects Laravel to your MySQL database

Make sure export_api_db exists in phpMyAdmin



## STEP 3: Create Model + Migration

We will export products data.

Command:


```

php artisan make:model Product -m

```

Explanation:

Creates Product model → app/Models/Product.php

Creates Migration → database/migrations/xxxx_create_products_table.php



### Migration File

 database/migrations/xxxx_create_products_table.php
```

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->decimal('price', 8, 2);
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```


Run migration:
```
php artisan migrate
```

Explanation:

Creates products table in database



app/model/Product.php
```

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Allow mass assignment for these fields
    protected $fillable = [
        'name',
        'sku',
        'price',
        'quantity',
    ];

 

}

```

Explanation:

Allows mass assignment

Required for Product::create()




## STEP 4: Insert Sample Data (Manual)

Open phpMyAdmin → products table
```
INSERT INTO products (name, sku, price, quantity, created_at, updated_at) VALUES
('Product1', 'SKU001', 100.50, 10, NOW(), NOW()),
('Product2', 'SKU002', 250.00, 5, NOW(), NOW()),
('Product3', 'SKU003', 75.99, 20, NOW(), NOW());
```

Explanation:

Inserts 3 sample products manually

Data can be exported via API



## STEP 5: Create API Controller

Command:



```
php artisan make:controller Api/ProductsExportController
```

Explanation:

Handles API logic

Keeps code organized



Controller Code

app/Http/Controllers/Api/ProductsExportController.php

```
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductsExportController extends Controller
{
    //  Export Products as JSON
    public function exportJson()
    {
        $products = Product::all();

        return response()->json([
            'status' => true,
            'data'   => $products
        ], Response::HTTP_OK);
    }

    // Export Products as CSV
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

    //  Export Products as Excel
    public function exportExcel()
    {
        return Excel::download(
            new ProductsExport,
            'products_export.xlsx'
        );
    }
}

```

## STEP 6: Install Excel Package

Laravel 12 supports this perfectly.

Command:


```
composer require maatwebsite/excel
```

Explanation:

Compatible with Laravel 12

Used for Excel exports



## STEP 7: Create Export Class

Command:


```
php artisan make:export ProductsExport --model=Product
```


Export Class Code

app/Exports/ProductsExport.php

```

<?php

namespace App\Exports;

use App\Models\Product;   //  CORRECT
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
```

Explanation:

collection() → Fetch data

headings() → Column headers



## STEP 8: Define API Routes

routes/api.php
```
<?php

use App\Http\Controllers\Api\ProductsExportController;

Route::get('/export/products/json', [ProductsExportController::class, 'exportJson']);
Route::get('/export/products/csv', [ProductsExportController::class, 'exportCsv']);
Route::get('/export/products/excel', [ProductsExportController::class, 'exportExcel']);
```

Explanation:

Defines URLs for API

JSON, CSV, Excel accessible separately



## STEP 9: Test API (Important) & Output

 ### JSON Export
```
http://127.0.0.1:8000/api/export/products/json

```

<img width="1397" height="875" alt="Screenshot 2025-12-23 110854" src="https://github.com/user-attachments/assets/8f1ee6be-d691-4f07-9965-af23a1ef81d5" />


 ### CSV Download
```
http://127.0.0.1:8000/api/export/products/csv
```

<img width="1381" height="907" alt="Screenshot 2025-12-23 110928" src="https://github.com/user-attachments/assets/a435b830-842d-43e5-b347-f97c1b283e8b" />



 ### Excel Download
```
http://127.0.0.1:8000/api/export/products/excel
```

<img width="1919" height="1022" alt="Screenshot 2025-12-23 102210" src="https://github.com/user-attachments/assets/e0fb971e-2d33-4358-9216-61e38950065b" />



Explanation:

Open URLs in browser or Postman

JSON → API response

CSV/Excel → Download files


---


# Final Project Structure

```
PHP_Laravel12_ExportData_Using_Api
│
├── app
│   ├── Exports
│   │   └── ProductsExport.php
│   ├── Http
│   │   └── Controllers
│   │       └── Api
│   │           └── ProductsExportController.php
│   └── Models
│       └── Product.php
│
├── routes
│   └── api.php
│
├── database
│   └── migrations
│       └── xxxx_create_products_table.php
```
