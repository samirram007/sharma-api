<?php

use Illuminate\Support\Facades\Route;
use Modules\Post\Controllers\Api\PostController;

Route::apiResource('posts', PostController::class);
