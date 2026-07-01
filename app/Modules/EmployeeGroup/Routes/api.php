<?php

use Illuminate\Support\Facades\Route;
use Modules\EmployeeGroup\Controllers\Api\EmployeeGroupController;

Route::apiResource('employee_groups', EmployeeGroupController::class)->middleware(['jwt.cookies']);
