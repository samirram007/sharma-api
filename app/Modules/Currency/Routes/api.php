<?php

use Illuminate\Support\Facades\Route;
use Modules\Currency\Controllers\Api\CurrencyController;

Route::apiResource('currencies', CurrencyController::class)->middleware([
    'jwt.cookies',
    'feature.permission:CURRENCY_MENU_VIEW',
]);
