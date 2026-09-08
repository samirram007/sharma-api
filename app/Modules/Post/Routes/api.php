<?php

use Illuminate\Support\Facades\Route;
use Modules\Post\Controllers\Api\PostController;

Route::apiResource('posts', PostController::class)->middleware([
    'jwt.cookies',
    'feature.permission:POST_MENU_VIEW',
]);
