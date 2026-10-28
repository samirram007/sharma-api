<?php

namespace App\Support;

/**
 * Feature codes guarding the delivery lock (postpone) workflow.
 *
 * Seeded by Modules\Voucher\Database\Seeders\DeliveryLockFeatureSeeder and
 * assigned per role through Roles & Permissions. Roles in
 * RequireFeaturePermission::SUPERADMIN_ROLE_CODES bypass all three.
 *
 * Keep in sync with the frontend FEATURES map
 * (sharma-frontend/src/data/features.ts) and the codes used by
 * voucher/shared/delivery-lock-permissions.ts.
 */
class DeliveryLockFeatures
{
    /** See the postponed / released state (chip, dialog, exports). */
    public const VIEW = 'DELIVERY_LOCK_VIEW';

    /** Postpone a delivery (create or update the lock reason). */
    public const POSTPONE = 'DELIVERY_LOCK_POSTPONE';

    /** Release a postponed delivery. */
    public const RELEASE = 'DELIVERY_LOCK_RELEASE';
}
