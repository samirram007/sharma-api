<?php

use Illuminate\Support\Facades\Route;
use Modules\VoucherType\Controllers\Api\VoucherTypeController;

Route::apiResource('voucher_types', VoucherTypeController::class)
    ->middleware('jwt.cookies');
