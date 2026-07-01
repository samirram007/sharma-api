<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Controllers\Api\EmployeeController;

Route::apiResource('employees', EmployeeController::class)->middleware(['jwt.cookies']);
