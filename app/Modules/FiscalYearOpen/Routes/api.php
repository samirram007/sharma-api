<?php

use Illuminate\Support\Facades\Route;
use App\Modules\FiscalYearOpen\Controllers\Api\FiscalYearOpenController;

Route::middleware('jwt.cookies')->group(function () {
    Route::get('fiscal-years/{newFiscalYear}/open-preview/{previousFiscalYear}', [FiscalYearOpenController::class, 'preview']);
    Route::post('fiscal-years/open', [FiscalYearOpenController::class, 'open']);
});
