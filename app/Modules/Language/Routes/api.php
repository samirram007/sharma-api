<?php

use Illuminate\Support\Facades\Route;
use Modules\Language\Controllers\Api\LanguageController;

Route::apiResource('languages', LanguageController::class)->middleware([
    'jwt.cookies',
    'feature.permission:LANGUAGE_MENU_VIEW',
]);
