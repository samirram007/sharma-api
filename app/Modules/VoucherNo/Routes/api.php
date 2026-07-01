<?php

use Illuminate\Support\Facades\Route;
use Modules\VoucherNo\Controllers\Api\VoucherNoController;

Route::apiResource('voucher_nos', VoucherNoController::class)->middleware(['jwt.cookies']);
Route::post('voucher_nos/get_voucher_no', [VoucherNoController::class, 'getVoucherNo'])->middleware(['jwt.cookies']);
