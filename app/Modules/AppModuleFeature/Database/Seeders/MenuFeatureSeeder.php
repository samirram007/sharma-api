<?php

namespace Modules\AppModuleFeature\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppModuleFeature\Models\AppModuleFeature;

class MenuFeatureSeeder extends Seeder
{
    public function run(): void
    {
        // All menu-level feature codes referenced by MenuSeeder and route guards.
        // These are grouped under the Administration module (10008) as they control
        // navigation/menu access across the entire application.
        $adminModuleId = 10008;

        $menuFeatures = [
            // ── General ──────────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'General Menu',          'code' => 'GENERAL_MENU_VIEW',          'icon' => 'LayoutDashboard'],
            ['app_module_id' => $adminModuleId, 'name' => 'Dashboard Menu',        'code' => 'DASHBOARD_MENU_VIEW',        'icon' => 'LayoutDashboard'],

            // ── Transactions ─────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Transaction Menu',      'code' => 'TRANSACTION_MENU_VIEW',      'icon' => 'Book'],
            ['app_module_id' => $adminModuleId, 'name' => 'Accounts Menu',         'code' => 'ACCOUNTS_MENU_VIEW',         'icon' => 'Book'],
            ['app_module_id' => $adminModuleId, 'name' => 'Vouchers Menu',         'code' => 'VOUCHERS_MENU_VIEW',         'icon' => 'ClipboardList'],
            ['app_module_id' => $adminModuleId, 'name' => 'Received (GRN) Menu',   'code' => 'RECEIPT_NOTE_MENU_VIEW',     'icon' => 'TruckDelivery'],
            ['app_module_id' => $adminModuleId, 'name' => 'Delivery Note Menu',    'code' => 'DELIVERY_NOTE_MENU_VIEW',    'icon' => 'Truck'],
            ['app_module_id' => $adminModuleId, 'name' => 'Conversion Menu',        'code' => 'CONVERSION_MENU_VIEW',       'icon' => 'ClipboardType'],
            ['app_module_id' => $adminModuleId, 'name' => 'Physical Stock Menu',    'code' => 'PHYSICAL_STOCK_MENU_VIEW',   'icon' => 'Checklist'],
            ['app_module_id' => $adminModuleId, 'name' => 'Opening Stock Menu',     'code' => 'OPENING_STOCK_MENU_VIEW',    'icon' => 'PackageOpen'],
            ['app_module_id' => $adminModuleId, 'name' => 'Day Book Menu',         'code' => 'DAYBOOK_MENU_VIEW',          'icon' => 'Book'],

            // ── Masters ──────────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Master Menu',           'code' => 'MASTER_MENU_VIEW',           'icon' => 'NotebookTabs'],
            ['app_module_id' => $adminModuleId, 'name' => 'Organization Menu',     'code' => 'ORGANIZATION_MENU_VIEW',     'icon' => 'Building2'],
            ['app_module_id' => $adminModuleId, 'name' => 'Company Menu',          'code' => 'COMPANY_MENU_VIEW',          'icon' => 'Building2'],
            ['app_module_id' => $adminModuleId, 'name' => 'Fiscal Year Menu',      'code' => 'FISCAL_YEAR_MENU_VIEW',      'icon' => 'ClipboardList'],
            ['app_module_id' => $adminModuleId, 'name' => 'Currency Menu',         'code' => 'CURRENCY_MENU_VIEW',         'icon' => 'Coin'],
            ['app_module_id' => $adminModuleId, 'name' => 'Country Menu',          'code' => 'COUNTRY_MENU_VIEW',          'icon' => 'Map'],
            ['app_module_id' => $adminModuleId, 'name' => 'State Menu',            'code' => 'STATE_MENU_VIEW',            'icon' => 'MapPin'],
            ['app_module_id' => $adminModuleId, 'name' => 'Chart of Accounts Menu', 'code' => 'CHART_OF_ACCOUNTS_MENU_VIEW', 'icon' => 'ListDetails'],
            ['app_module_id' => $adminModuleId, 'name' => 'Account Ledger Menu',   'code' => 'ACCOUNT_LEDGER_MENU_VIEW',   'icon' => 'Notebook'],
            ['app_module_id' => $adminModuleId, 'name' => 'Voucher Type Menu',     'code' => 'VOUCHER_TYPE_MENU_VIEW',     'icon' => 'Receipt'],
            ['app_module_id' => $adminModuleId, 'name' => 'Party Menu',            'code' => 'PARTY_MENU_VIEW',            'icon' => 'Users'],
            ['app_module_id' => $adminModuleId, 'name' => 'Distributor Menu',      'code' => 'DISTRIBUTOR_MENU_VIEW',      'icon' => 'TruckDelivery'],
            ['app_module_id' => $adminModuleId, 'name' => 'Supplier Menu',         'code' => 'SUPPLIER_MENU_VIEW',         'icon' => 'Truck'],
            ['app_module_id' => $adminModuleId, 'name' => 'Transporter Menu',      'code' => 'TRANSPORTER_MENU_VIEW',      'icon' => 'Route'],
            ['app_module_id' => $adminModuleId, 'name' => 'Inventory Menu',        'code' => 'INVENTORY_MENU_VIEW',        'icon' => 'WarehouseIcon'],
            ['app_module_id' => $adminModuleId, 'name' => 'Stock Item Menu',       'code' => 'STOCK_ITEM_MENU_VIEW',       'icon' => 'Packages'],
            ['app_module_id' => $adminModuleId, 'name' => 'Stock Group Menu',      'code' => 'STOCK_GROUP_MENU_VIEW',      'icon' => 'ListDetails'],
            ['app_module_id' => $adminModuleId, 'name' => 'Stock Category Menu',   'code' => 'STOCK_CATEGORY_MENU_VIEW',   'icon' => 'Checklist'],
            ['app_module_id' => $adminModuleId, 'name' => 'Stock Unit Menu',       'code' => 'STOCK_UNIT_MENU_VIEW',       'icon' => 'Scale'],
            ['app_module_id' => $adminModuleId, 'name' => 'Godown Menu',           'code' => 'GODOWN_MENU_VIEW',           'icon' => 'BuildingWarehouse'],

            // ── Payroll ──────────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Payroll Menu',          'code' => 'PAYROLL_MENU_VIEW',          'icon' => 'HandCoinsIcon'],
            ['app_module_id' => $adminModuleId, 'name' => 'Employee Menu',         'code' => 'EMPLOYEE_MENU_VIEW',         'icon' => 'Users'],
            ['app_module_id' => $adminModuleId, 'name' => 'Department Menu',       'code' => 'DEPARTMENT_MENU_VIEW',       'icon' => 'Building2'],
            ['app_module_id' => $adminModuleId, 'name' => 'Designation Menu',      'code' => 'DESIGNATION_MENU_VIEW',      'icon' => 'UserCheck'],

            // ── Statutory ────────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Statutory Menu',        'code' => 'STATUTORY_MENU_VIEW',        'icon' => 'LandmarkIcon'],

            // ── Miscellaneous ────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Miscellaneous Menu',    'code' => 'MISCELLANEOUS_MENU_VIEW',    'icon' => 'MichelinStar'],
            ['app_module_id' => $adminModuleId, 'name' => 'Delivery Places Menu',  'code' => 'DELIVERY_PLACES_MENU_VIEW',  'icon' => 'LocationBolt'],
            ['app_module_id' => $adminModuleId, 'name' => 'Delivery Routes Menu',  'code' => 'DELIVERY_ROUTES_MENU_VIEW',  'icon' => 'Route2'],

            // ── Administration ───────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Administration Menu',   'code' => 'ADMINISTRATION_MENU_VIEW',   'icon' => 'Settings'],
            ['app_module_id' => $adminModuleId, 'name' => 'User Menu',             'code' => 'USER_MENU_VIEW',             'icon' => 'Users'],
            ['app_module_id' => $adminModuleId, 'name' => 'Role Menu',             'code' => 'ROLE_MENU_VIEW',             'icon' => 'Radar2'],
            ['app_module_id' => $adminModuleId, 'name' => 'Permission Menu',       'code' => 'PERMISSION_MENU_VIEW',       'icon' => 'PremiumRights'],
            ['app_module_id' => $adminModuleId, 'name' => 'App Module Menu',       'code' => 'APP_MODULE_MENU_VIEW',       'icon' => 'Apps'],
            ['app_module_id' => $adminModuleId, 'name' => 'App Feature Menu',      'code' => 'APP_FEATURE_MENU_VIEW',      'icon' => 'PaperBag'],
            ['app_module_id' => $adminModuleId, 'name' => 'Menu Manager View',     'code' => 'MENU_MANAGER_VIEW',          'icon' => 'ListDetails'],
            ['app_module_id' => $adminModuleId, 'name' => 'Menu View',             'code' => 'MENU_VIEW',                  'icon' => 'ListDetails'],
            ['app_module_id' => $adminModuleId, 'name' => 'App Module Feature View', 'code' => 'APP_MODULE_FEATURE_VIEW',   'icon' => 'PaperBag'],

            // ── Reports ──────────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Reports Menu',          'code' => 'REPORTS_MENU_VIEW',          'icon' => 'ChartBar'],
            ['app_module_id' => $adminModuleId, 'name' => 'Balance Sheet Menu',    'code' => 'BALANCE_SHEET_MENU_VIEW',    'icon' => 'Scale'],
            ['app_module_id' => $adminModuleId, 'name' => 'Profit Loss Menu',      'code' => 'PROFIT_LOSS_MENU_VIEW',      'icon' => 'TrendingUp'],
            ['app_module_id' => $adminModuleId, 'name' => 'Day Book Self Menu',    'code' => 'DAYBOOK_SELF_MENU_VIEW',     'icon' => 'UserCheck'],
            ['app_module_id' => $adminModuleId, 'name' => 'Receipt Book Menu',     'code' => 'RECEIPTBOOK_MENU_VIEW',      'icon' => 'Receipt'],
            ['app_module_id' => $adminModuleId, 'name' => 'Distributor Book Menu', 'code' => 'DISTRIBUTORBOOK_MENU_VIEW',  'icon' => 'TruckDelivery'],
            ['app_module_id' => $adminModuleId, 'name' => 'Stock Summary Menu',    'code' => 'STOCKSUMMARY_MENU_VIEW',     'icon' => 'Packages'],
            ['app_module_id' => $adminModuleId, 'name' => 'Opening Entry Report',  'code' => 'OPENING_ENTRY_REPORT_MENU_VIEW', 'icon' => 'DoorEnter'],
            ['app_module_id' => $adminModuleId, 'name' => 'Freight Menu',          'code' => 'FREIGHT_MENU_VIEW',          'icon' => 'Truck'],
            ['app_module_id' => $adminModuleId, 'name' => 'Freight Report Menu',   'code' => 'FREIGHT_REPORT_MENU_VIEW',   'icon' => 'FileText'],
            ['app_module_id' => $adminModuleId, 'name' => 'Receipt Note Report Menu', 'code' => 'RECEIPT_NOTE_REPORT_MENU_VIEW', 'icon' => 'FileText'],
            ['app_module_id' => $adminModuleId, 'name' => 'Delivery Note Report Menu', 'code' => 'DELIVERY_NOTE_REPORT_MENU_VIEW', 'icon' => 'FileText'],
            ['app_module_id' => $adminModuleId, 'name' => 'Conversion Journal Report Menu', 'code' => 'CONVERSION_JOURNAL_REPORT_MENU_VIEW', 'icon' => 'FileText'],
            ['app_module_id' => $adminModuleId, 'name' => 'Manufacturing Journal Report Menu', 'code' => 'MANUFACTURING_JOURNAL_REPORT_MENU_VIEW', 'icon' => 'BuildingFactory'],
            ['app_module_id' => $adminModuleId, 'name' => 'Opening Stock Reports Menu', 'code' => 'OPENING_STOCK_REPORT_MENU_VIEW', 'icon' => 'Scale'],

            // ── Year-End ─────────────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Year End Process Menu', 'code' => 'YEAR_END_PROCESS_MENU_VIEW', 'icon' => 'Archive'],
            ['app_module_id' => $adminModuleId, 'name' => 'Close Fiscal Year Menu', 'code' => 'CLOSE_FISCAL_YEAR_MENU_VIEW', 'icon' => 'Archive'],
            ['app_module_id' => $adminModuleId, 'name' => 'Opening Journal Menu',  'code' => 'OPENING_JOURNAL_MENU_VIEW',  'icon' => 'DoorEnter'],
            ['app_module_id' => $adminModuleId, 'name' => 'Opening Balance Setup Menu', 'code' => 'OPENING_BALANCE_MENU_VIEW', 'icon' => 'Scale'],

            // ── Settings & Help ──────────────────────────────────
            ['app_module_id' => $adminModuleId, 'name' => 'Settings Menu',         'code' => 'SETTINGS_MENU_VIEW',         'icon' => 'Settings'],
            ['app_module_id' => $adminModuleId, 'name' => 'Help Center Menu',      'code' => 'HELP_CENTER_MENU_VIEW',      'icon' => 'Help'],
        ];

        foreach ($menuFeatures as $feature) {
            AppModuleFeature::updateOrCreate(
                ['code' => $feature['code']],
                $feature
            );
        }

        $this->command->info('MenuFeatureSeeder: '.count($menuFeatures).' menu-level features seeded.');
    }
}
