<?php

use Illuminate\Support\Facades\Route;
use App\Modules\PhysicalStockCount\Controllers\Api\PhysicalStockCountController;

Route::middleware('jwt.cookies')->group(function () {
    Route::apiResource('physical-stock-counts', PhysicalStockCountController::class);

    // Custom operations
    Route::post('physical-stock-counts/{physicalStockCount}/populate', [PhysicalStockCountController::class, 'populateSystemQuantities']);
    Route::post('physical-stock-counts/{physicalStockCount}/verify', [PhysicalStockCountController::class, 'verify']);
    Route::post('physical-stock-counts/{physicalStockCount}/generate-adjustment', [PhysicalStockCountController::class, 'generateAdjustment']);
});
