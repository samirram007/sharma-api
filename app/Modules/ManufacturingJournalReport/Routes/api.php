<?php

use Illuminate\Support\Facades\Route;
use Modules\ManufacturingJournalReport\Controllers\Api\ManufacturingJournalReportController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::get('manufacturing_journal_report', [ManufacturingJournalReportController::class, 'index']);
    Route::get('manufacturing_journal_report/grouped-by-stock-item', [ManufacturingJournalReportController::class, 'groupedByStockItem']);
    Route::get('manufacturing_journal_report/grouped-by-godown', [ManufacturingJournalReportController::class, 'groupedByGodown']);
    Route::get('manufacturing_journal_report/grouped-by-date', [ManufacturingJournalReportController::class, 'groupedByDate']);
});
