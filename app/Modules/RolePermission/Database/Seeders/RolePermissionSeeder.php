<?php

namespace Modules\RolePermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\RolePermission\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (id: 10000) gets ALL permissions granted.
        $superAdminRoleId = 10000;
        $adminRoleId = 10001;

        $allFeatures = AppModuleFeature::all();

        if ($allFeatures->isEmpty()) {
            $this->command->warn('No AppModuleFeatures found. Run AppModuleFeatureSeeder and MenuFeatureSeeder first.');

            return;
        }

        $count = 0;
        foreach ($allFeatures as $feature) {
            // Grant all permissions to Super Admin
            RolePermission::updateOrCreate(
                ['role_id' => $superAdminRoleId, 'app_module_feature_id' => $feature->id],
                ['is_allowed' => true]
            );
            $count++;

            // Grant all permissions to Admin as well
            RolePermission::updateOrCreate(
                ['role_id' => $adminRoleId, 'app_module_feature_id' => $feature->id],
                ['is_allowed' => true]
            );
            $count++;
        }

        $this->command->info("RolePermissionSeeder: {$count} role permissions seeded for Super Admin and Admin roles.");
    }
}
