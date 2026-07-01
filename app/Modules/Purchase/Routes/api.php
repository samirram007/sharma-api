<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Controllers\Api\PurchaseController;

Route::apiResource('purchases', PurchaseController::class)->middleware(['jwt.cookies']);
