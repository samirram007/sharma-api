<?php

use Illuminate\Support\Facades\Route;
use Modules\AppNotification\Controllers\Api\AppNotificationController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::get('app-notifications', [AppNotificationController::class, 'index']);
    Route::get('app-notifications/my', [AppNotificationController::class, 'forCurrentUser']);
    Route::get('app-notifications/unread-count', [AppNotificationController::class, 'unreadCount']);
    Route::get('app-notifications/{id}', [AppNotificationController::class, 'show']);
    Route::post('app-notifications', [AppNotificationController::class, 'store']);
    Route::patch('app-notifications/{id}/read', [AppNotificationController::class, 'markAsRead']);
    Route::patch('app-notifications/read-all', [AppNotificationController::class, 'markAllAsRead']);
    Route::delete('app-notifications/{id}', [AppNotificationController::class, 'destroy']);

    // Voucher / Freight specific routes
    Route::get('app-notifications/voucher/{voucherId}', [AppNotificationController::class, 'forVoucher']);
    Route::post('app-notifications/validate-freight', [AppNotificationController::class, 'validateFreight']);
});
