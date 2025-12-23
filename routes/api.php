<?php

use App\Http\Controllers\Api\ProductsExportController;

Route::get('/export/products/json', [ProductsExportController::class, 'exportJson']);
Route::get('/export/products/csv', [ProductsExportController::class, 'exportCsv']);
Route::get('/export/products/excel', [ProductsExportController::class, 'exportExcel']);
