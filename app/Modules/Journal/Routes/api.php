<?php

use Illuminate\Support\Facades\Route;
use Modules\Journal\Controllers\Api\JournalController;

Route::apiResource('journals', JournalController::class);
