<?php

use Illuminate\Support\Facades\Route;
use Modules\AppMaintenance\Controllers\Api\AppMaintenanceController;

Route::apiResource('app_maintenances', AppMaintenanceController::class)->middleware([
    'jwt.cookies',
    'feature.permission:APP_MAINTENANCE_MENU_VIEW',
]);
