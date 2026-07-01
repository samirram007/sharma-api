<?php

use Illuminate\Support\Facades\Route;
use Modules\StockJournalStorageUnitEntryPurge\Controllers\Api\StockJournalStorageUnitEntryPurgeController;

Route::apiResource('sjsu_entry_purges', StockJournalStorageUnitEntryPurgeController::class)->middleware(['jwt.cookies']);
