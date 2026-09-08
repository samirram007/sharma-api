<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Controllers\Api\SettingController;

Route::apiResource('settings', SettingController::class)->middleware([
    'jwt.cookies',
    'feature.permission:SETTINGS_MENU_VIEW',
]);
