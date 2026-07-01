<?php

use Illuminate\Support\Facades\Route;
use Modules\StockItemPrice\Controllers\Api\StockItemPriceController;

Route::apiResource('stock_item_prices', StockItemPriceController::class)->middleware(['jwt.cookies']);
