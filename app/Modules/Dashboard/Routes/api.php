<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Controllers\Api\DashboardController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('dashboard/zone_wise', [DashboardController::class, 'zoneWise']);
    Route::get('dashboard/godown_wise', [DashboardController::class, 'godownWise']);
    Route::get('dashboard/transporter_wise', [DashboardController::class, 'transporterWise']);
    Route::get('dashboard/user_wise', [DashboardController::class, 'userWise']);
});
