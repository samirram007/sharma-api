<?php

use Illuminate\Support\Facades\Route;
use Modules\StockItem\Controllers\Api\StockItemController;

Route::apiResource('stock_items', StockItemController::class)->middleware(['jwt.cookies']);
Route::get('purchasable_stock_items', [StockItemController::class, 'purchasable_stock_items'])->middleware(['jwt.cookies']);
