<?php

use Illuminate\Support\Facades\Route;
use Modules\Receipt\Controllers\Api\ReceiptController;

Route::apiResource('receipts', ReceiptController::class)->middleware(['jwt.cookies']);
