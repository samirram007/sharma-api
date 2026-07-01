<?php

use Illuminate\Support\Facades\Route;
use Modules\VoucherClassification\Controllers\Api\VoucherClassificationController;

Route::apiResource('voucher_classifications', VoucherClassificationController::class)->middleware(['jwt.cookies']);
