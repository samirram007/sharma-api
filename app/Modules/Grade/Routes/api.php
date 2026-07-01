<?php

use Illuminate\Support\Facades\Route;
use Modules\Grade\Controllers\Api\GradeController;

Route::apiResource('grades', GradeController::class)->middleware(['jwt.cookies']);
