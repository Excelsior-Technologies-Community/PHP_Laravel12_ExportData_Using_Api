<?php

use App\Http\Controllers\Api\ProductsExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:10,1'])->group(function () {
    Route::get('/export/products/json', [ProductsExportController::class, 'exportJson']);
    Route::get('/export/products/csv', [ProductsExportController::class, 'exportCsv']);
    Route::get('/export/products/excel', [ProductsExportController::class, 'exportExcel']);
    Route::get('/export/products/pdf', [ProductsExportController::class, 'exportPdf']);
});