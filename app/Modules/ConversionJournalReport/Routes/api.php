<?php

use Illuminate\Support\Facades\Route;
use Modules\ConversionJournalReport\Controllers\Api\ConversionJournalReportController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::get('conversion_journal_report', [ConversionJournalReportController::class, 'index']);
    Route::get('conversion_journal_report/grouped-by-stock-item', [ConversionJournalReportController::class, 'groupedByStockItem']);
    Route::get('conversion_journal_report/grouped-by-godown', [ConversionJournalReportController::class, 'groupedByGodown']);
    Route::get('conversion_journal_report/grouped-by-date', [ConversionJournalReportController::class, 'groupedByDate']);
});
