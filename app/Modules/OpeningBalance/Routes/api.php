<?php

use Illuminate\Support\Facades\Route;
use Modules\OpeningBalance\Controllers\Api\OpeningBalanceController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::get('opening-balance/setup-data', [OpeningBalanceController::class, 'setupData']);
    Route::get('opening-balance/status', [OpeningBalanceController::class, 'status']);
    Route::post('opening-balance', [OpeningBalanceController::class, 'store']);
});
