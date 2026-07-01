<?php

use Illuminate\Support\Facades\Route;
use Modules\State\Controllers\Api\StateController;

Route::apiResource('states', StateController::class);
