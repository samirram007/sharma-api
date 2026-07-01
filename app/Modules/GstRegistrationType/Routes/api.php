<?php

use Illuminate\Support\Facades\Route;
use Modules\GstRegistrationType\Controllers\Api\GstRegistrationTypeController;

Route::apiResource('gst_registration_types', GstRegistrationTypeController::class)->middleware(['jwt.cookies']);
