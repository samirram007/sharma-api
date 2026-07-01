<?php

use Illuminate\Support\Facades\Route;
use Modules\Language\Controllers\Api\LanguageController;

Route::apiResource('languages', LanguageController::class);
