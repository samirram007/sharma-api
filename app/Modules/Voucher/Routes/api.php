<?php

use App\Support\DeliveryLockFeatures;
use Illuminate\Support\Facades\Route;
use Modules\Voucher\Controllers\Api\DeliveryLockController;
use Modules\Voucher\Controllers\Api\VoucherController;

Route::get('vouchers/opening-stock/voucher-type', [VoucherController::class, 'openingStockVoucherType'])->middleware(['jwt.cookies']);
Route::get('vouchers/opening-stock/previous-year-closing', [VoucherController::class, 'previousYearClosingStock'])->middleware(['jwt.cookies']);
Route::apiResource('vouchers', VoucherController::class)->middleware(['jwt.cookies']);
// Route::get('vouchers', VoucherController::class)->middleware(['jwt.cookies']);
Route::get('vouchers/{id}/print', [VoucherController::class, 'print'])->middleware(['jwt.cookies']);

// Delivery lock / unlock endpoint — body: { id, lock, payload: { reason, note } }.
// One endpoint covers both actions, so the route admits either lock right and
// the controller enforces the exact one (POSTPONE vs RELEASE).
Route::post('delivery-locks', [DeliveryLockController::class, 'store'])
    ->middleware(['jwt.cookies', 'feature.permission:'.DeliveryLockFeatures::POSTPONE.','.DeliveryLockFeatures::RELEASE]);
