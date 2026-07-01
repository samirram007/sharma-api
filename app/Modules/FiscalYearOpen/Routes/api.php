<?php

use Illuminate\Support\Facades\Route;
use Modules\FiscalYearOpen\Controllers\Api\FiscalYearOpenController;

use Modules\FiscalYearOpen\Controllers\Api\OpeningEntryReportController;

Route::middleware('jwt.cookies')->group(function () {
    Route::get('fiscal-years/{newFiscalYear}/open-preview/{previousFiscalYear}', [FiscalYearOpenController::class, 'preview']);
    Route::post('fiscal-years/open', [FiscalYearOpenController::class, 'open']);
    Route::get('fiscal-years/{fiscalYear}/opening-entry-report', [OpeningEntryReportController::class, 'show']);
    Route::get('fiscal-years/{fiscalYear}/opening-entry-report/grouped-by-ledger', [OpeningEntryReportController::class, 'groupedByLedger']);
});
