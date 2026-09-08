<?php

use Illuminate\Support\Facades\Route;
use Modules\Module\Controllers\Api\ModuleController;

Route::apiResource('modules', ModuleController::class)->middleware([
    'jwt.cookies',
    'feature.permission:MODULE_MENU_VIEW',
]);
