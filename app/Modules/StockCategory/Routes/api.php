<?php

use Illuminate\Support\Facades\Route;
use Modules\StockCategory\Controllers\Api\StockCategoryController;

Route::apiResource('stock_categories', StockCategoryController::class)->middleware(['jwt.cookies']);
