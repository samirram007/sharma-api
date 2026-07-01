<?php

use Illuminate\Support\Facades\Route;
use Modules\StockJournalStorageUnitEntry\Controllers\Api\StockJournalStorageUnitEntryController;

Route::apiResource('stock_journal_storage_unit_entries', StockJournalStorageUnitEntryController::class)->middleware(['jwt.cookies']);
