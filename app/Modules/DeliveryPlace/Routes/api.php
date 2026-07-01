<?php

use Illuminate\Support\Facades\Route;
use Modules\DeliveryPlace\Controllers\Api\DeliveryPlaceController;

Route::apiResource('delivery_places', DeliveryPlaceController::class)->middleware(['jwt.cookies']);
