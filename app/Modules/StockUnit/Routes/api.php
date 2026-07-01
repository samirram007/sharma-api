<?php

use Illuminate\Support\Facades\Route;
use Modules\StockUnit\Controllers\Api\StockUnitController;

Route::apiResource('stock_units', StockUnitController::class)->middleware(['jwt.cookies']);
