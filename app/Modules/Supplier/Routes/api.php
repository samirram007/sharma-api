<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Controllers\Api\SupplierController;

Route::apiResource('suppliers', SupplierController::class)->middleware(['jwt.cookies']);
