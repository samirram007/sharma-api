<?php

use Illuminate\Support\Facades\Route;
use Modules\StockJournalSerialNoEntry\Controllers\Api\StockJournalSerialNoEntryController;

Route::apiResource('stock_journal_serial_no_entries', StockJournalSerialNoEntryController::class)->middleware(['jwt.cookies']);
