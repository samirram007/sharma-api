<?php

namespace Modules\Voucher\Database\Seeders;

use App\Support\DeliveryLockFeatures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\RolePermission\Models\RolePermission;

/**
 * Seeds the delivery-lock (postpone) feature codes so they show up in
 * Roles & Permissions and can be granted per role:
 *
 *   DELIVERY_LOCK_VIEW      — see the postponed / released state
 *   DELIVERY_LOCK_POSTPONE  — postpone a delivery (lock)
 *   DELIVERY_LOCK_RELEASE   — release a postponed delivery (unlock)
 *
 * Default grants: VIEW goes to every role (seeing a delivery is postponed is
 * harmless and mirrors what every role saw before the permission existed), so
 * a newly created or custom role never silently loses the state. POSTPONE and
 * RELEASE stay opt-in — granted to Admin and Developer only. Super Admin
 * bypasses the checks entirely
 * (RequireFeaturePermission::SUPERADMIN_ROLE_CODES).
 *
 * Idempotent: firstOrCreate on the code + updateOrCreate on the grants.
 */
class DeliveryLockFeatureSeeder extends Seeder
{
    public const FEATURES = [
        DeliveryLockFeatures::VIEW => [
            'name' => 'Delivery Lock View',
            'description' => 'See whether a delivery is postponed (locked) or released.',
            // '*' = every role (the permission can still be revoked per role).
            'roles' => '*',
        ],
        DeliveryLockFeatures::POSTPONE => [
            'name' => 'Delivery Lock Postpone',
            'description' => 'Postpone a delivery with a reason (lock it).',
            'roles' => ['ADMIN', 'DEVELOPER'],
        ],
        DeliveryLockFeatures::RELEASE => [
            'name' => 'Delivery Lock Release',
            'description' => 'Release a postponed delivery (unlock it).',
            'roles' => ['ADMIN', 'DEVELOPER'],
        ],
    ];

    public function run(): void
    {
        $appModuleId = $this->resolveAppModuleId();

        foreach (self::FEATURES as $code => $feature) {
            $model = AppModuleFeature::firstOrCreate(
                ['code' => $code],
                [
                    'app_module_id' => $appModuleId,
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'icon' => 'Lock',
                ]
            );

            $roleIds = $feature['roles'] === '*'
                ? DB::table('roles')->pluck('id')
                : DB::table('roles')->whereIn('code', $feature['roles'])->pluck('id');

            foreach ($roleIds as $roleId) {
                RolePermission::updateOrCreate(
                    ['role_id' => $roleId, 'app_module_feature_id' => $model->id],
                    ['is_allowed' => true]
                );
            }
        }

        $this->command?->info('DeliveryLockFeatureSeeder: '.count(self::FEATURES).' feature(s) ensured.');
    }

    /**
     * Attach the features to the module the Freight / Day Book menu lives in,
     * so the Roles & Permissions screen groups them with the transaction
     * features. Falls back to the lowest module id.
     */
    private function resolveAppModuleId(): int
    {
        $existing = AppModuleFeature::query()
            ->whereIn('code', ['FREIGHT_MENU_VIEW', 'DAYBOOK_MENU_VIEW'])
            ->value('app_module_id');

        return (int) ($existing ?? AppModuleFeature::query()->min('app_module_id'));
    }
}
