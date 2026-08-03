<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\Api\AuthController;

// Unprotected auth routes (registration, login, social, and cookie-clearing logout)
Route::group(['prefix' => 'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/user-profile', function (): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'User profile fetched successfully.',
            'data' => [],
        ]);
    });

    // Clear cookie even if token is already expired/absent
    Route::get('/clean_logout', [AuthController::class, 'clean_logout']);
    Route::post('/clean_logout', [AuthController::class, 'clean_logout']);

    // Social
    Route::get('/{provider}', [AuthController::class, 'socialRedirect'])
        ->where('provider', 'google|github');
    Route::get('/{provider}/callback', [AuthController::class, 'socialCallback'])
        ->where('provider', 'google|github');
});

// Protected auth routes (require valid JWT)
Route::middleware(['jwt.cookies'])->group(function () {
    Route::group(['prefix' => 'auth'], function ($router) {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'profile']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::get('/user', [AuthController::class, 'profile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});
