<?php

use Illuminate\Support\Facades\Route;
use Modules\CostAllocationRule\Controllers\Api\CostAllocationRuleController;

Route::apiResource('cost_allocation_rules', CostAllocationRuleController::class)->middleware(['jwt.cookies']);
