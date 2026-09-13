<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AppModuleFeature\Models\AppModuleFeature;

/**
 * The MenuSeeder shipped the Delivery Vehicles menu entry with
 * is_visible = false ("currently hidden from the sidebar"), which the frontend
 * menu-route guard treats as UNROUTABLE — visiting
 * /masters/miscellaneous/delivery_vehicles renders the Access Denied (403)
 * page even for admins.
 *
 * This migration brings existing databases in line with the corrected seeder:
 * 1. Un-hides the Delivery Vehicles menu row (and fixes its description).
 * 2. Ensures the MISCELLANEOUS_MENU_VIEW feature exists (the seeder linked the
 *    entry to it; DBs seeded before that feature existed have a null linkage).
 * 3. Grants MISCELLANEOUS_MENU_VIEW to the full-access roles (admin 10001,
 *    developer 10002) — RolePermissionSeeder only grants features that
 *    existed when it last ran, so DBs seeded earlier are missing this grant.
 *
 * Idempotent: every step is guarded, so it can be re-run safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menu')) {
            return;
        }

        // 1. Ensure the feature row exists and grab its id.
        $featureId = AppModuleFeature::query()->where('code', 'MISCELLANEOUS_MENU_VIEW')->value('id');

        if (! $featureId) {
            // app_module_id is NOT NULL: reuse the Administration module when
            // it exists, create it when the table is empty (fresh databases
            // run migrations BEFORE seeders), and bail out when neither is
            // possible — MenuFeatureSeeder will seed this feature anyway.
            $adminModuleId = Schema::hasTable('app_modules')
                ? DB::table('app_modules')->where('code', 'ADMIN')->value('id')
                : null;

            if (! $adminModuleId) {
                return;
            }

            $feature = AppModuleFeature::query()->create([
                'app_module_id' => $adminModuleId,
                'name' => 'Miscellaneous Menu',
                'code' => 'MISCELLANEOUS_MENU_VIEW',
                'icon' => 'MichelinStar',
            ]);
            $featureId = $feature->id;
        }

        // 2. Un-hide the Delivery Vehicles menu entry + link it to the feature.
        $exists = DB::table('menu')
            ->where('route', '/masters/miscellaneous/delivery_vehicles')
            ->exists();

        if ($exists) {
            DB::table('menu')
                ->where('route', '/masters/miscellaneous/delivery_vehicles')
                ->update([
                    'is_visible' => true,
                    'status' => 'active',
                    'app_module_feature_id' => DB::raw('COALESCE(app_module_feature_id, '.((int) $featureId).')'),
                    'description' => 'Maintain delivery vehicle master records.',
                ]);
        } else {
            // Menu row missing entirely (e.g. seeded before the entry was
            // added) — create it under the Miscellaneous group if that exists.
            $parentId = DB::table('menu')
                ->where('menu_name', 'Miscellaneous')
                ->whereNull('parent_id')
                ->value('id');

            if ($parentId) {
                DB::table('menu')->insert([
                    'app_module_feature_id' => $featureId,
                    'menu_name' => 'Delivery Vehicles',
                    'route' => '/masters/miscellaneous/delivery_vehicles',
                    'icon' => 'Truck',
                    'parent_id' => $parentId,
                    'sort_order' => 30,
                    'status' => 'active',
                    'is_visible' => true,
                    'is_group' => false,
                    'description' => 'Maintain delivery vehicle master records.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Grant the feature to the full-access roles (super admin bypasses
        // permission checks; employee gets it via RolePermissionSeeder rules).
        if (Schema::hasTable('role_permissions')) {
            foreach ([10001, 10002] as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'app_module_feature_id' => $featureId],
                    ['is_allowed' => true]
                );
            }
        }
    }

    public function down(): void
    {
        // No destructive down: restoring is_visible = false would re-block the
        // page this migration exists to un-block.
    }
};
