<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Controllers\Api\CountryController;

Route::apiResource('countries', CountryController::class);
