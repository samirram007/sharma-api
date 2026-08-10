<?php

use Illuminate\Support\Facades\Route;
use Modules\Voucher\Controllers\Api\VoucherController;

Route::get('vouchers/opening-stock/voucher-type', [VoucherController::class, 'openingStockVoucherType'])->middleware(['jwt.cookies']);
Route::get('vouchers/opening-stock/previous-year-closing', [VoucherController::class, 'previousYearClosingStock'])->middleware(['jwt.cookies']);
Route::apiResource('vouchers', VoucherController::class)->middleware(['jwt.cookies']);
// Route::get('vouchers', VoucherController::class)->middleware(['jwt.cookies']);
Route::get('vouchers/{id}/print', [VoucherController::class, 'print'])->middleware(['jwt.cookies']);
