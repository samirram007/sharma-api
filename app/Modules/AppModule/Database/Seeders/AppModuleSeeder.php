<?php

namespace Modules\AppModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppModule\Models\AppModule;

class AppModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'id' => 10000,
                'name' => 'User Management',
                'code' => 'USER_MGMT',
                'description' => 'Manage users, roles, and permissions',
                'status' => 'active',
                'icon' => 'IconUsers',
            ],
            [
                'id' => 10001,
                'name' => 'Finance',
                'code' => 'FINANCE',
                'description' => 'Handles financial transactions, invoices, and ledgers',
                'status' => 'active',
                'icon' => 'FaMoneyBill',
            ],
            [
                'id' => 10002,
                'name' => 'Inventory',
                'code' => 'INVENTORY',
                'description' => 'Manages stock items, batches, and warehouse data',
                'status' => 'active',
                'icon' => 'IconPackages',
            ],
            [
                'id' => 10003,
                'name' => 'Sales',
                'code' => 'SALES',
                'description' => 'Sales orders, customers, and billing operations',
                'status' => 'active',
                'icon' => 'FaCartPlus',
            ],
            [
                'id' => 10004,
                'name' => 'Purchase',
                'code' => 'PURCHASE',
                'description' => 'Supplier management and purchase order tracking',
                'status' => 'active',
                'icon' => 'FaShoppingBag',
            ],
            [
                'id' => 10005,
                'name' => 'Reports',
                'code' => 'REPORTS',
                'description' => 'System-wide reporting and analytics',
                'status' => 'active',
                'icon' => 'FaChartLine',
            ],
            [
                'id' => 10006,
                'name' => 'Payroll',
                'code' => 'PAYROLL',
                'description' => 'Employee management, salary processing, and payroll',
                'status' => 'active',
                'icon' => 'FaMoneyCheck',
            ],
            [
                'id' => 10007,
                'name' => 'Masters',
                'code' => 'MASTERS',
                'description' => 'Core master data: chart of accounts, stock, party, organization',
                'status' => 'active',
                'icon' => 'FaIndustry',
            ],
            [
                'id' => 10008,
                'name' => 'Administration',
                'code' => 'ADMIN',
                'description' => 'System administration: users, roles, permissions, settings',
                'status' => 'active',
                'icon' => 'FaUserShield',
            ],
            [
                'id' => 10009,
                'name' => 'Transactions',
                'code' => 'TRANSACTIONS',
                'description' => 'Daily transactions: vouchers, payments, receipts, journals',
                'status' => 'active',
                'icon' => 'FaExchangeAlt',
            ],
        ];

        foreach ($modules as $module) {
            AppModule::updateOrCreate(
                ['id' => $module['id']],
                $module
            );
        }
    }
}
