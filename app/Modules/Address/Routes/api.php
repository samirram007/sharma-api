<?php

use Illuminate\Support\Facades\Route;
use Modules\Address\Controllers\Api\AddressController;

Route::apiResource('addresses', AddressController::class)->middleware(['jwt.cookies']);
