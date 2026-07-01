<?php

use Illuminate\Support\Facades\Route;
use Modules\AppModuleFeature\Controllers\Api\AppModuleFeatureController;

use Modules\AppModuleFeature\Controllers\Api\MenuController;

Route::apiResource('app_module_features', AppModuleFeatureController::class)->middleware(['jwt.cookies']);
Route::get('/role/{role_id}/module-features/{module_id}', [AppModuleFeatureController::class, 'getModuleFeaturesByRole'])->middleware(['jwt.cookies']);
Route::get('/role/{role_id}/menu-permissions', [AppModuleFeatureController::class, 'getRoleMenuPermissions'])->middleware(['jwt.cookies']);

