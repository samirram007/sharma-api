<?php

namespace App\Enums;

/**
 * Preset reasons a delivery note can be locked (postponed) with from the
 * Freight screen. The free-text detail travels in vouchers.delivery_lock_note.
 *
 * Keep in sync with the frontend preset list in
 * sharma-frontend/src/features/modules/voucher/shared/delivery-lock.ts.
 */
enum DeliveryLockReason: string
{
    case VehicleFailure = 'vehicle_failure';
    case UncertainSituation = 'uncertain_situation';
    case PartyUnavailable = 'party_unavailable';
    case Other = 'other';

    public static function getValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
