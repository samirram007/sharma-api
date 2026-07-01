<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Controllers\Api\CustomerController;

Route::apiResource('customers', CustomerController::class)->middleware(['jwt.cookies']);
