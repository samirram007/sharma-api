<?php

use Illuminate\Support\Facades\Route;
use Modules\DocumentManager\Controllers\Api\DocumentManagerController;

Route::middleware('jwt.cookies')->prefix('document-manager')->group(function (): void {
    Route::get('browse', [DocumentManagerController::class, 'browse']);
    Route::get('search', [DocumentManagerController::class, 'search']);
    Route::get('meta', [DocumentManagerController::class, 'meta']);
    Route::get('share-targets', [DocumentManagerController::class, 'shareTargets']);
    Route::get('shared-with-me', [DocumentManagerController::class, 'sharedWithMe']);
    Route::get('shared-by-me', [DocumentManagerController::class, 'sharedByMe']);
    Route::get('folders', [DocumentManagerController::class, 'folders']);
    Route::post('folders', [DocumentManagerController::class, 'storeFolder']);
    Route::post('upload', [DocumentManagerController::class, 'upload']);
    Route::post('conflicts', [DocumentManagerController::class, 'conflicts']);
    Route::post('shortcuts', [DocumentManagerController::class, 'storeShortcut']);
    Route::get('shortcuts/{shortcut}/resolve', [DocumentManagerController::class, 'resolveShortcut']);
    Route::delete('shortcuts/{shortcut}', [DocumentManagerController::class, 'destroyShortcut']);
    Route::patch('nodes/{node}', [DocumentManagerController::class, 'updateNode']);
    Route::post('nodes/{node}/copy', [DocumentManagerController::class, 'copy']);
    Route::delete('nodes/{node}', [DocumentManagerController::class, 'destroy']);
    Route::get('nodes/{node}/stats', [DocumentManagerController::class, 'nodeStats']);
    Route::get('nodes/{node}/download', [DocumentManagerController::class, 'download']);
    Route::get('nodes/{node}/preview', [DocumentManagerController::class, 'preview']);
    Route::put('nodes/{node}/shares', [DocumentManagerController::class, 'syncShares']);
});
