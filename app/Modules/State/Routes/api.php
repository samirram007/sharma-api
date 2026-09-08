<?php

use Illuminate\Support\Facades\Route;
use Modules\State\Controllers\Api\StateController;

Route::apiResource('states', StateController::class)->middleware([
    'jwt.cookies',
    'feature.permission:STATE_MENU_VIEW',
]);
