<?php

use App\Support\DeliveryLockFeatures;
use Illuminate\Support\Facades\Route;
use Modules\Freight\Controllers\Api\FreightController;

// Route::apiResource('freights', FreightController::class)->middleware(['jwt.cookies']);
Route::post('freights', [FreightController::class, 'store'])->middleware(['jwt.cookies']);
// Delivery lock (postpone) — maintained from the Freight screen. Postponing
// and releasing are separate permissions (Roles & Permissions).
Route::post('freights/{id}/lock', [FreightController::class, 'lock'])
    ->middleware(['jwt.cookies', 'feature.permission:'.DeliveryLockFeatures::POSTPONE]);
Route::delete('freights/{id}/lock', [FreightController::class, 'unlock'])
    ->middleware(['jwt.cookies', 'feature.permission:'.DeliveryLockFeatures::RELEASE]);
Route::get('freights/delivery_note', [FreightController::class, 'delivery_note'])->middleware(['jwt.cookies']);
Route::get('freights/freight', [FreightController::class, 'index'])->middleware(['jwt.cookies']);
Route::get('freights/freight/{id}', [FreightController::class, 'show'])->middleware(['jwt.cookies']);

// Delivery lock (postpone) report — shows all postponed (locked) delivery notes
// with lock details (reason, note, locked_at, locked_by).
Route::get('/freights_delivery_lock_report', [FreightController::class, 'delivery_lock_report'])
    ->middleware(['jwt.cookies']);

// Godown-wise report
Route::get('/freights_godown_wise', [FreightController::class, 'godown_wise'])->middleware(['jwt.cookies']);
// Zone-wise report
Route::get('/freights_zone_wise', [FreightController::class, 'zone_wise'])->middleware(['jwt.cookies']);
// Delivery note zone-wise report
Route::get('/freights_delivery_note_zone_wise', [FreightController::class, 'delivery_note_zone_wise'])->middleware(['jwt.cookies']);
// Delivery note godown-wise report
Route::get('/freights_delivery_note_godown_wise', [FreightController::class, 'delivery_note_godown_wise'])->middleware(['jwt.cookies']);
// Transporter-wise report
Route::get('/freights_transporter_wise', [FreightController::class, 'transporter_wise'])->middleware(['jwt.cookies']);
// Transporter item-wise report
Route::get('/freights_transporter_item_wise', [FreightController::class, 'transporter_item_wise'])->middleware(['jwt.cookies']);
// Vehicle-wise report
Route::get('/freights_vehicle_wise', [FreightController::class, 'vehicle_wise'])->middleware(['jwt.cookies']);
// Billing preference report
Route::get('/freights_billing_preference', [FreightController::class, 'billing_preference'])->middleware(['jwt.cookies']);
// Voucher-wise report
Route::get('/freights_voucher_wise', [FreightController::class, 'voucher_wise'])->middleware(['jwt.cookies']);