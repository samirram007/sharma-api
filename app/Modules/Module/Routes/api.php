<?php

use Illuminate\Support\Facades\Route;
use Modules\Module\Controllers\Api\ModuleController;

Route::apiResource('modules', ModuleController::class);
