<?php

use Illuminate\Support\Facades\Route;
use Modules\Distributor\Controllers\Api\DistributorController;

Route::apiResource('distributors', DistributorController::class)->middleware(['jwt.cookies']);
