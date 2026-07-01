<?php

use Illuminate\Support\Facades\Route;
use Modules\Branch\Controllers\Api\BranchController;

Route::apiResource('branches', BranchController::class)->middleware(['jwt.cookies']);
