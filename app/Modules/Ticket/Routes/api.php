<?php

use Illuminate\Support\Facades\Route;
use Modules\Ticket\Controllers\Api\TicketController;

Route::middleware(['jwt.cookies'])->group(function (): void {
    Route::apiResource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/responses', [TicketController::class, 'addResponse']);
    Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
});
