<?php

use Illuminate\Support\Facades\Route;
use Modules\Department\Controllers\Api\DepartmentController;

Route::apiResource('departments', DepartmentController::class)->middleware(['jwt.cookies']);
