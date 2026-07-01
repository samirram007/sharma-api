<?php

use Illuminate\Support\Facades\Route;
use Modules\Uqc\Controllers\Api\UqcController;

Route::apiResource('uqcs', UqcController::class)->middleware(['jwt.cookies']);
