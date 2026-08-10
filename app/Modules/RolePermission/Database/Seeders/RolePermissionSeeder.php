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
        $employeeRoleId = 10004;

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

        // Employee role: grant transaction menu-view permissions so the
        // Transactions > Accounts > Vouchers/Day Book sidebar renders for
        // Employee accounts. Other menu sections can be granted via the
        // Roles & Permissions UI as needed.
        $employeeMenuCodes = [
            'TRANSACTION_MENU_VIEW',
            'ACCOUNTS_MENU_VIEW',
            'VOUCHERS_MENU_VIEW',
            'RECEIPT_NOTE_MENU_VIEW',
            'DELIVERY_NOTE_MENU_VIEW',
            'CONVERSION_MENU_VIEW',
            'PHYSICAL_STOCK_MENU_VIEW',
            'OPENING_STOCK_MENU_VIEW',
            'DAYBOOK_MENU_VIEW',
            'FREIGHT_MENU_VIEW',
        ];
        $employeeFeatures = AppModuleFeature::whereIn('code', $employeeMenuCodes)->get();
        if ($employeeFeatures->isEmpty()) {
            $this->command->warn('No transaction menu features found — run MenuFeatureSeeder first. Employee menu permissions were not granted.');
        }
        foreach ($employeeFeatures as $feature) {
            RolePermission::updateOrCreate(
                ['role_id' => $employeeRoleId, 'app_module_feature_id' => $feature->id],
                ['is_allowed' => true]
            );
            $count++;
        }

        // ── Module-specific Employee roles (10007-10012) ──────────────
        // Each role sees: Dashboard (General), its own transaction module,
        // and its related reports — in both the side menu and top menu.
        $moduleRolePermissions = [
            [
                'role_id' => 10007, // Received Note Employee
                'features' => [
                    'GENERAL_MENU_VIEW',
                    'DASHBOARD_MENU_VIEW',
                    'TRANSACTION_MENU_VIEW',
                    'RECEIPT_NOTE_MENU_VIEW',
                    'REPORTS_MENU_VIEW',
                    'RECEIPT_NOTE_REPORT_MENU_VIEW',
                ],
            ],
            [
                'role_id' => 10008, // Delivery Note Employee
                'features' => [
                    'GENERAL_MENU_VIEW',
                    'DASHBOARD_MENU_VIEW',
                    'TRANSACTION_MENU_VIEW',
                    'DELIVERY_NOTE_MENU_VIEW',
                    'REPORTS_MENU_VIEW',
                    'DELIVERY_NOTE_REPORT_MENU_VIEW',
                ],
            ],
            [
                'role_id' => 10009, // Freight Employee
                'features' => [
                    'GENERAL_MENU_VIEW',
                    'DASHBOARD_MENU_VIEW',
                    'TRANSACTION_MENU_VIEW',
                    'FREIGHT_MENU_VIEW',
                    'REPORTS_MENU_VIEW',
                    'FREIGHT_REPORT_MENU_VIEW',
                ],
            ],
            [
                'role_id' => 10010, // Conversion Employee
                'features' => [
                    'GENERAL_MENU_VIEW',
                    'DASHBOARD_MENU_VIEW',
                    'TRANSACTION_MENU_VIEW',
                    'CONVERSION_MENU_VIEW',
                    'REPORTS_MENU_VIEW',
                    'CONVERSION_JOURNAL_REPORT_MENU_VIEW',
                    'MANUFACTURING_JOURNAL_REPORT_MENU_VIEW',
                ],
            ],
            [
                'role_id' => 10011, // Physical Stock Employee
                'features' => [
                    'GENERAL_MENU_VIEW',
                    'DASHBOARD_MENU_VIEW',
                    'TRANSACTION_MENU_VIEW',
                    'PHYSICAL_STOCK_MENU_VIEW',
                    'REPORTS_MENU_VIEW',
                    'STOCKSUMMARY_MENU_VIEW',
                    'OPENING_ENTRY_REPORT_MENU_VIEW',
                ],
            ],
            [
                'role_id' => 10012, // Opening Stock Employee
                'features' => [
                    'GENERAL_MENU_VIEW',
                    'DASHBOARD_MENU_VIEW',
                    'TRANSACTION_MENU_VIEW',
                    'OPENING_STOCK_MENU_VIEW',
                    'REPORTS_MENU_VIEW',
                    'OPENING_STOCK_REPORT_MENU_VIEW',
                    'OPENING_ENTRY_REPORT_MENU_VIEW',
                    'OPENING_BALANCE_MENU_VIEW',
                ],
            ],
        ];

        foreach ($moduleRolePermissions as $roleGrant) {
            $features = AppModuleFeature::whereIn('code', $roleGrant['features'])->get();
            if ($features->isEmpty()) {
                $this->command->warn('No features found for role '.$roleGrant['role_id'].' — run MenuFeatureSeeder first.');

                continue;
            }
            foreach ($features as $feature) {
                RolePermission::updateOrCreate(
                    ['role_id' => $roleGrant['role_id'], 'app_module_feature_id' => $feature->id],
                    ['is_allowed' => true]
                );
                $count++;
            }
        }

        $this->command->info("RolePermissionSeeder: {$count} role permissions seeded for Super Admin, Admin, Employee, and module-specific Employee roles.");
    }
}
