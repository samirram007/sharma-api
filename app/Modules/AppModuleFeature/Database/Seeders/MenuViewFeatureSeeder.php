<?php

namespace Modules\AppModuleFeature\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\RolePermission\Models\RolePermission;

/**
 * Seeds the menu-view features that existed as UI screens but had no
 * app_module_features row (Language, Journal, Post, AppMaintenance, Module
 * CRUD screens are authenticated-only, admin-managed screens without their
 * own menu entries), then grants the full menu-view feature set to the
 * non-superadmin roles — mirroring how MenuFeatureSeeder features are
 * granted by RolePermissionSeeder.
 *
 * Idempotent: firstOrCreate on the feature code + updateOrCreate on the
 * role permission rows, so it can be re-run safely.
 */
class MenuViewFeatureSeeder extends Seeder
{
    /**
     * Features the route-level `feature.permission` gates rely on but which
     * MenuFeatureSeeder does not seed. These mirror the *_MENU_VIEW codes.
     */
    public const EXTRA_FEATURES = [
        ['code' => 'LANGUAGE_MENU_VIEW', 'name' => 'Language Menu'],
        ['code' => 'JOURNAL_MENU_VIEW', 'name' => 'Journal Menu'],
        ['code' => 'POST_MENU_VIEW', 'name' => 'Post Menu'],
        ['code' => 'APP_MAINTENANCE_MENU_VIEW', 'name' => 'App Maintenance Menu'],
        ['code' => 'MODULE_MENU_VIEW', 'name' => 'Module Menu'],
    ];

    public function run(): void
    {
        $adminModuleId = AppModuleFeature::query()->min('app_module_id');

        foreach (self::EXTRA_FEATURES as $feature) {
            $model = AppModuleFeature::firstOrCreate(
                ['code' => $feature['code']],
                [
                    'app_module_id' => $adminModuleId,
                    'name' => $feature['name'],
                    'icon' => 'List',
                ]
            );

            // Grant to admin (10001) + developer (10002) + employee (10004) —
            // the same roles RolePermissionSeeder treats as menu-visible.
            // Super Admin (10000) bypasses middleware checks entirely.
            foreach ([10001, 10002, 10004] as $roleId) {
                RolePermission::updateOrCreate(
                    ['role_id' => $roleId, 'app_module_feature_id' => $model->id],
                    ['is_allowed' => true]
                );
            }
        }

        $this->command->info('MenuViewFeatureSeeder: '.count(self::EXTRA_FEATURES).' feature(s) ensured + granted.');
    }
}
