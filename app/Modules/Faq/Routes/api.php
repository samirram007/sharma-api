<?php

use Illuminate\Support\Facades\Route;
use Modules\Faq\Controllers\Api\FaqController;

Route::apiResource('faqs', FaqController::class)->middleware(['jwt.cookies']);
