<?php

use Illuminate\Support\Facades\Route;
use Modules\AppMaintenance\Controllers\Api\AppMaintenanceController;

Route::apiResource('app_maintenances', AppMaintenanceController::class);
