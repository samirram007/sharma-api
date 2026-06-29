<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FiscalYearClose\Controllers\Api\FiscalYearCloseController;

Route::middleware('jwt.cookies')->group(function () {
    Route::get('fiscal-years/{fiscalYear}/close-preview', [FiscalYearCloseController::class, 'preview']);
    Route::post('fiscal-years/{fiscalYear}/close', [FiscalYearCloseController::class, 'close']);
    Route::post('fiscal-years/{fiscalYear}/reopen', [FiscalYearCloseController::class, 'reopen']);
});
