<?php

use Illuminate\Support\Facades\Route;
use Modules\Salary\Controllers\Api\SalaryController;

Route::apiResource('salaries', SalaryController::class)->middleware(['jwt.cookies']);
