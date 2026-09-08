<?php

use Illuminate\Support\Facades\Route;
use Modules\Journal\Controllers\Api\JournalController;

Route::apiResource('journals', JournalController::class)->middleware([
    'jwt.cookies',
    'feature.permission:JOURNAL_MENU_VIEW',
]);
