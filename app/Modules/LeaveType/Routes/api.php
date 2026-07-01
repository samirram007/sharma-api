<?php

use Illuminate\Support\Facades\Route;
use Modules\LeaveType\Controllers\Api\LeaveTypeController;

Route::apiResource('leave_types', LeaveTypeController::class)->middleware(['jwt.cookies']);
