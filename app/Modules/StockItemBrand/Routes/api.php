<?php

use Illuminate\Support\Facades\Route;
use Modules\StockItemBrand\Controllers\Api\StockItemBrandController;

Route::apiResource('stock_item_brands', StockItemBrandController::class)->middleware(['jwt.cookies']);
