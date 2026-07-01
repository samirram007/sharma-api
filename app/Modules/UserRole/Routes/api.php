<?php

use Illuminate\Support\Facades\Route;
use Modules\UserRole\Controllers\Api\UserRoleController;

Route::apiResource('user_roles', UserRoleController::class)->middleware(['jwt.cookies']);
