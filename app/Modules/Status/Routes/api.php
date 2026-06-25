<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Status\Controllers\Api\StatusController;

Route::apiResource('statuses', StatusController::class)->middleware(['jwt.cookies']);
