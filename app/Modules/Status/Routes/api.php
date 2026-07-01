<?php

use Illuminate\Support\Facades\Route;
use Modules\Status\Controllers\Api\StatusController;

Route::apiResource('statuses', StatusController::class)->middleware(['jwt.cookies']);
