<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Controllers\Api\PaymentController;

Route::apiResource('payments', PaymentController::class)->middleware(['jwt.cookies']);

Route::get('freight_payments/{freight_id}', [PaymentController::class, 'freightPayments'])
    ->middleware(['jwt.cookies']);
