<?php

use Illuminate\Support\Facades\Route;
use Modules\Agent\Controllers\Api\AgentController;

Route::apiResource('agents', AgentController::class)->middleware(['jwt.cookies']);
