<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\Api\UserController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::apiResource('users', UserController::class);

    // Notification Preferences
    Route::get('user/notification-preferences', [UserController::class, 'notificationPreferences']);
    Route::put('user/notification-preferences', [UserController::class, 'updateNotificationPreferences']);
});
