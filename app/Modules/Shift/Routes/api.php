<?php

use Illuminate\Support\Facades\Route;
use Modules\Shift\Controllers\Api\ShiftController;

Route::apiResource('shifts', ShiftController::class)->middleware(['jwt.cookies']);
