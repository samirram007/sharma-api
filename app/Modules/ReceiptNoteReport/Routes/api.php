<?php

use Illuminate\Support\Facades\Route;
use Modules\ReceiptNoteReport\Controllers\Api\ReceiptNoteReportController;

Route::middleware(['jwt.cookies'])->group(function () {
    Route::get('receipt_note_report', [ReceiptNoteReportController::class, 'index']);
    Route::get('receipt_note_report/grouped-by-ledger', [ReceiptNoteReportController::class, 'groupedByLedger']);
    Route::get('receipt_note_report/grouped-by-date', [ReceiptNoteReportController::class, 'groupedByDate']);
    Route::get('receipt_note_report/grouped-by-stock-item', [ReceiptNoteReportController::class, 'groupedByStockItem']);
    Route::get('receipt_note_report/grouped-by-godown', [ReceiptNoteReportController::class, 'groupedByGodown']);
});
