<?php

use Illuminate\Support\Facades\Route;
use Modules\Document\Controllers\Api\DocumentController;

Route::apiResource('documents', DocumentController::class)->middleware(['jwt.cookies']);
