<?php

use Illuminate\Support\Facades\Route;
use Modules\Currency\Controllers\Api\CurrencyController;

Route::apiResource('currencies', CurrencyController::class);
