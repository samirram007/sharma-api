<?php

use Illuminate\Support\Facades\Route;
use Modules\Holiday\Controllers\Api\HolidayController;

Route::apiResource('holidays', HolidayController::class)->middleware(['jwt.cookies']);
