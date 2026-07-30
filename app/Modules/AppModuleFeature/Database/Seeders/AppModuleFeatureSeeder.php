<?php

namespace Modules\AppModuleFeature\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppModuleFeature\Models\AppModuleFeature;

class AppModuleFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            // ── User Management (id: 10000) ────────────────────────────────
            ['app_module_id' => 10000, 'name' => 'View',      'code' => 'USER_MGMT_VIEW',   'icon' => 'List'],
            ['app_module_id' => 10000, 'name' => 'Create',    'code' => 'USER_MGMT_CREATE', 'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10000, 'name' => 'Edit',      'code' => 'USER_MGMT_EDIT',   'icon' => 'ClipboardPen'],
            ['app_module_id' => 10000, 'name' => 'Delete',    'code' => 'USER_MGMT_DELETE', 'icon' => 'FaTrash'],

            // ── Finance (id: 10001) ───────────────────────────────────────
            ['app_module_id' => 10001, 'name' => 'View',      'code' => 'FINANCE_VIEW',     'icon' => 'ReceiptText'],
            ['app_module_id' => 10001, 'name' => 'Create',    'code' => 'FINANCE_CREATE',   'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10001, 'name' => 'Edit',      'code' => 'FINANCE_EDIT',     'icon' => 'ClipboardPen'],
            ['app_module_id' => 10001, 'name' => 'Delete',    'code' => 'FINANCE_DELETE',   'icon' => 'FaTrash'],
            ['app_module_id' => 10001, 'name' => 'Approve',   'code' => 'FINANCE_APPROVE',  'icon' => 'CheckCheck'],

            // ── Inventory (id: 10002) ─────────────────────────────────────
            ['app_module_id' => 10002, 'name' => 'View',      'code' => 'INVENTORY_VIEW',     'icon' => 'Package'],
            ['app_module_id' => 10002, 'name' => 'Create',    'code' => 'INVENTORY_CREATE',   'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10002, 'name' => 'Edit',      'code' => 'INVENTORY_EDIT',     'icon' => 'ClipboardPen'],
            ['app_module_id' => 10002, 'name' => 'Delete',    'code' => 'INVENTORY_DELETE',   'icon' => 'FaTrash'],
            ['app_module_id' => 10002, 'name' => 'Transfer',  'code' => 'INVENTORY_TRANSFER', 'icon' => 'FaExchangeAlt'],

            // ── Sales (id: 10003) ─────────────────────────────────────────
            ['app_module_id' => 10003, 'name' => 'View',      'code' => 'SALES_VIEW',       'icon' => 'ShoppingCart'],
            ['app_module_id' => 10003, 'name' => 'Create',    'code' => 'SALES_CREATE',     'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10003, 'name' => 'Edit',      'code' => 'SALES_EDIT',       'icon' => 'ClipboardPen'],
            ['app_module_id' => 10003, 'name' => 'Delete',    'code' => 'SALES_DELETE',     'icon' => 'FaTrash'],
            ['app_module_id' => 10003, 'name' => 'Dispatch',  'code' => 'SALES_DISPATCH',   'icon' => 'FaTruck'],

            // ── Purchase (id: 10004) ──────────────────────────────────────
            ['app_module_id' => 10004, 'name' => 'View',      'code' => 'PURCHASE_VIEW',     'icon' => 'FaShoppingBag'],
            ['app_module_id' => 10004, 'name' => 'Create',    'code' => 'PURCHASE_CREATE',   'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10004, 'name' => 'Edit',      'code' => 'PURCHASE_EDIT',     'icon' => 'ClipboardPen'],
            ['app_module_id' => 10004, 'name' => 'Delete',    'code' => 'PURCHASE_DELETE',   'icon' => 'FaTrash'],
            ['app_module_id' => 10004, 'name' => 'Receive',   'code' => 'PURCHASE_RECEIVE',  'icon' => 'FaFileImport'],

            // ── Reports (id: 10005) ───────────────────────────────────────
            ['app_module_id' => 10005, 'name' => 'View',      'code' => 'REPORTS_VIEW',     'icon' => 'ChartBar'],
            ['app_module_id' => 10005, 'name' => 'Export',    'code' => 'REPORTS_EXPORT',   'icon' => 'FaFileExport'],
            ['app_module_id' => 10005, 'name' => 'Print',     'code' => 'REPORTS_PRINT',    'icon' => 'FaFileInvoice'],

            // ── Payroll (id: 10006) ───────────────────────────────────────
            ['app_module_id' => 10006, 'name' => 'View',      'code' => 'PAYROLL_VIEW',     'icon' => 'IconUsers'],
            ['app_module_id' => 10006, 'name' => 'Create',    'code' => 'PAYROLL_CREATE',   'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10006, 'name' => 'Edit',      'code' => 'PAYROLL_EDIT',     'icon' => 'ClipboardPen'],
            ['app_module_id' => 10006, 'name' => 'Delete',    'code' => 'PAYROLL_DELETE',   'icon' => 'FaTrash'],
            ['app_module_id' => 10006, 'name' => 'Process',   'code' => 'PAYROLL_PROCESS',  'icon' => 'FaMoneyCheck'],

            // ── Masters (id: 10007) ───────────────────────────────────────
            ['app_module_id' => 10007, 'name' => 'View',      'code' => 'MASTERS_VIEW',     'icon' => 'Building2'],
            ['app_module_id' => 10007, 'name' => 'Create',    'code' => 'MASTERS_CREATE',   'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10007, 'name' => 'Edit',      'code' => 'MASTERS_EDIT',     'icon' => 'ClipboardPen'],
            ['app_module_id' => 10007, 'name' => 'Delete',    'code' => 'MASTERS_DELETE',   'icon' => 'FaTrash'],

            // ── Administration (id: 10008) ────────────────────────────────
            ['app_module_id' => 10008, 'name' => 'View',      'code' => 'ADMIN_VIEW',       'icon' => 'Settings'],
            ['app_module_id' => 10008, 'name' => 'Create',    'code' => 'ADMIN_CREATE',     'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10008, 'name' => 'Edit',      'code' => 'ADMIN_EDIT',       'icon' => 'ClipboardPen'],
            ['app_module_id' => 10008, 'name' => 'Delete',    'code' => 'ADMIN_DELETE',     'icon' => 'FaTrash'],

            // ── Transactions (id: 10009) ──────────────────────────────────
            ['app_module_id' => 10009, 'name' => 'View',      'code' => 'TRANSACTIONS_VIEW',   'icon' => 'Receipt'],
            ['app_module_id' => 10009, 'name' => 'Create',    'code' => 'TRANSACTIONS_CREATE', 'icon' => 'FaPlusSquare'],
            ['app_module_id' => 10009, 'name' => 'Edit',      'code' => 'TRANSACTIONS_EDIT',   'icon' => 'ClipboardPen'],
            ['app_module_id' => 10009, 'name' => 'Delete',    'code' => 'TRANSACTIONS_DELETE', 'icon' => 'FaTrash'],
            ['app_module_id' => 10009, 'name' => 'Approve',   'code' => 'TRANSACTIONS_APPROVE', 'icon' => 'CheckCheck'],
            ['app_module_id' => 10009, 'name' => 'Cancel',    'code' => 'TRANSACTIONS_CANCEL', 'icon' => 'FaBan'],
        ];

        foreach ($features as $feature) {
            AppModuleFeature::updateOrCreate(
                ['app_module_id' => $feature['app_module_id'], 'name' => $feature['name']],
                $feature
            );
        }
    }
}
