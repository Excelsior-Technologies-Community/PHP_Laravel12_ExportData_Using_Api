<?php
// routes/api.php

use App\Http\Controllers\Api\ProductsExportController;

Route::prefix('export')->group(function () {
    // Simple exports (GET)
    Route::get('/products/json', [ProductsExportController::class, 'exportJson']);
    Route::get('/products/csv', [ProductsExportController::class, 'exportCsv']);
    Route::get('/products/excel', [ProductsExportController::class, 'exportExcel']);
    Route::get('/products/pdf', [ProductsExportController::class, 'exportPdf']);
    
    // NEW: Advanced exports (POST for complex filters)
    Route::post('/products/{format}', [ProductsExportController::class, 'export']);
    
    // NEW: Queue system
    Route::post('/products/queue/{format}', [ProductsExportController::class, 'queueExport']);
    Route::get('/export/status/{id}', [ProductsExportController::class, 'exportStatus'])->name('api.export.status');
    Route::get('/export/download/{id}', [ProductsExportController::class, 'downloadExport']);
    
    // NEW: ZIP export
    Route::post('/products/zip', [ProductsExportController::class, 'exportZip']);
    
    // NEW: History & schedules
    Route::get('/history', [ProductsExportController::class, 'exportHistory']);
    Route::post('/schedule', [ProductsExportController::class, 'scheduleExport']);
});