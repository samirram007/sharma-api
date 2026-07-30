<?php

namespace Modules\Menu\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\Menu\Models\Menu;

class MenuSeeder extends Seeder
{
    /** Cache feature code → ID lookups. */
    private array $featureIds = [];

    public function run(): void
    {
        // Clear existing entries so the seeder is idempotent
        Menu::truncate();

        // Build feature code → ID lookup from app_module_features
        $this->featureIds = AppModuleFeature::pluck('id', 'code')->toArray();

        // ── General ──────────────────────────────────────────────
        $general = $this->create([
            'menu_name' => 'General',
            'app_module_feature_code' => 'GENERAL_MENU_VIEW',
            'icon' => 'LayoutDashboard',
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create([
            'menu_name' => 'Dashboard',
            'route' => '/',
            'app_module_feature_code' => 'DASHBOARD_MENU_VIEW',
            'icon' => 'LayoutDashboard',
            'parent' => $general,
            'sort_order' => 10,
        ]);

        // ── Transactions ─────────────────────────────────────────
        $transactions = $this->create([
            'menu_name' => 'Transactions',
            'app_module_feature_code' => 'TRANSACTION_MENU_VIEW',
            'icon' => 'Book',
            'sort_order' => 20,
            'is_group' => true,
        ]);

        $accountsInTrans = $this->create([
            'menu_name' => 'Accounts',
            'app_module_feature_code' => 'ACCOUNTS_MENU_VIEW',
            'icon' => 'Book',
            'parent' => $transactions,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create([
            'menu_name' => 'Vouchers',
            'route' => '/transactions/vouchers',
            'app_module_feature_code' => 'VOUCHERS_MENU_VIEW',
            'icon' => 'ClipboardList',
            'parent' => $accountsInTrans,
            'sort_order' => 10,
        ]);

        $this->create([
            'menu_name' => 'Day Book',
            'route' => '/reports/day_book',
            'app_module_feature_code' => 'DAYBOOK_MENU_VIEW',
            'icon' => 'Book',
            'parent' => $accountsInTrans,
            'sort_order' => 20,
        ]);

        // ── Masters ──────────────────────────────────────────────
        $masters = $this->create([
            'menu_name' => 'Masters',
            'app_module_feature_code' => 'MASTER_MENU_VIEW',
            'icon' => 'NotebookTabs',
            'sort_order' => 30,
            'is_group' => true,
        ]);

        // Organization
        $org = $this->create([
            'menu_name' => 'Organization',
            'app_module_feature_code' => 'ORGANIZATION_MENU_VIEW',
            'icon' => 'Building2',
            'parent' => $masters,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Company', 'route' => '/masters/organization/company', 'app_module_feature_code' => 'COMPANY_MENU_VIEW', 'icon' => 'Building2', 'parent' => $org, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Branch', 'route' => '/masters/organization/branch', 'app_module_feature_code' => 'ORGANIZATION_MENU_VIEW', 'icon' => 'Building2', 'parent' => $org, 'sort_order' => 15, 'is_visible' => false]);
        $this->create(['menu_name' => 'Fiscal Year', 'route' => '/masters/organization/fiscal_year', 'app_module_feature_code' => 'FISCAL_YEAR_MENU_VIEW', 'icon' => 'ClipboardList', 'parent' => $org, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Currency', 'route' => '/masters/organization/currency', 'app_module_feature_code' => 'CURRENCY_MENU_VIEW', 'icon' => 'Coin', 'parent' => $org, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Country', 'route' => '/masters/organization/country', 'app_module_feature_code' => 'COUNTRY_MENU_VIEW', 'icon' => 'Map', 'parent' => $org, 'sort_order' => 40]);
        $this->create(['menu_name' => 'State', 'route' => '/masters/organization/state', 'app_module_feature_code' => 'STATE_MENU_VIEW', 'icon' => 'MapPin', 'parent' => $org, 'sort_order' => 50]);

        // Accounts
        $accts = $this->create([
            'menu_name' => 'Accounts',
            'app_module_feature_code' => 'ACCOUNTS_MENU_VIEW',
            'icon' => 'NotebookTabs',
            'parent' => $masters,
            'sort_order' => 20,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Chart of Accounts', 'route' => '/masters/accounts/account_group', 'app_module_feature_code' => 'CHART_OF_ACCOUNTS_MENU_VIEW', 'icon' => 'ListDetails', 'parent' => $accts, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Account Ledger', 'route' => '/masters/accounts/account_ledger', 'app_module_feature_code' => 'ACCOUNT_LEDGER_MENU_VIEW', 'icon' => 'Notebook', 'parent' => $accts, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Voucher Type', 'route' => '/masters/accounts/voucher_type', 'app_module_feature_code' => 'VOUCHER_TYPE_MENU_VIEW', 'icon' => 'Receipt', 'parent' => $accts, 'sort_order' => 30]);

        // Party
        $party = $this->create([
            'menu_name' => 'Party',
            'app_module_feature_code' => 'PARTY_MENU_VIEW',
            'icon' => 'Users',
            'parent' => $masters,
            'sort_order' => 30,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Distributor', 'route' => '/masters/party/distributor', 'app_module_feature_code' => 'DISTRIBUTOR_MENU_VIEW', 'icon' => 'TruckDelivery', 'parent' => $party, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Supplier', 'route' => '/masters/party/supplier', 'app_module_feature_code' => 'SUPPLIER_MENU_VIEW', 'icon' => 'Truck', 'parent' => $party, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Transporter', 'route' => '/masters/party/transporter', 'app_module_feature_code' => 'TRANSPORTER_MENU_VIEW', 'icon' => 'Route', 'parent' => $party, 'sort_order' => 30]);

        // Inventory
        $inv = $this->create([
            'menu_name' => 'Inventory',
            'app_module_feature_code' => 'INVENTORY_MENU_VIEW',
            'icon' => 'WarehouseIcon',
            'parent' => $masters,
            'sort_order' => 40,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Stock Item', 'route' => '/masters/inventory/stock_item', 'app_module_feature_code' => 'STOCK_ITEM_MENU_VIEW', 'icon' => 'Packages', 'parent' => $inv, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Stock Group', 'route' => '/masters/inventory/stock_group', 'app_module_feature_code' => 'STOCK_GROUP_MENU_VIEW', 'icon' => 'ListDetails', 'parent' => $inv, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Stock Category', 'route' => '/masters/inventory/stock_category', 'app_module_feature_code' => 'STOCK_CATEGORY_MENU_VIEW', 'icon' => 'Checklist', 'parent' => $inv, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Stock Unit', 'route' => '/masters/inventory/stock_unit', 'app_module_feature_code' => 'STOCK_UNIT_MENU_VIEW', 'icon' => 'Scale', 'parent' => $inv, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Godown', 'route' => '/masters/inventory/godown', 'app_module_feature_code' => 'GODOWN_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'parent' => $inv, 'sort_order' => 50]);

        // Payroll
        $payroll = $this->create([
            'menu_name' => 'Payroll',
            'app_module_feature_code' => 'PAYROLL_MENU_VIEW',
            'icon' => 'HandCoinsIcon',
            'parent' => $masters,
            'sort_order' => 50,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Employee', 'route' => '/masters/payroll/employee', 'app_module_feature_code' => 'EMPLOYEE_MENU_VIEW', 'icon' => 'Users', 'parent' => $payroll, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Department', 'route' => '/masters/payroll/department', 'app_module_feature_code' => 'DEPARTMENT_MENU_VIEW', 'icon' => 'Building2', 'parent' => $payroll, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Designation', 'route' => '/masters/payroll/designation', 'app_module_feature_code' => 'DESIGNATION_MENU_VIEW', 'icon' => 'UserCheck', 'parent' => $payroll, 'sort_order' => 30]);

        // Statutory
        $statutory = $this->create([
            'menu_name' => 'Statutory',
            'app_module_feature_code' => 'STATUTORY_MENU_VIEW',
            'icon' => 'LandmarkIcon',
            'parent' => $masters,
            'sort_order' => 60,
            'is_group' => true,
        ]);

        // Statutory items reuse same routes as Inventory but under Statutory group
        $this->create(['menu_name' => 'Stock Item', 'route' => '/masters/inventory/stock_item', 'app_module_feature_code' => 'STOCK_ITEM_MENU_VIEW', 'icon' => 'Notebook', 'parent' => $statutory, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Stock Group', 'route' => '/masters/inventory/stock_group', 'app_module_feature_code' => 'STOCK_GROUP_MENU_VIEW', 'icon' => 'ListDetails', 'parent' => $statutory, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Stock Category', 'route' => '/masters/inventory/stock_category', 'app_module_feature_code' => 'STOCK_CATEGORY_MENU_VIEW', 'icon' => 'Checklist', 'parent' => $statutory, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Stock Unit', 'route' => '/masters/inventory/stock_unit', 'app_module_feature_code' => 'STOCK_UNIT_MENU_VIEW', 'icon' => 'Scale', 'parent' => $statutory, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Godown', 'route' => '/masters/inventory/godown', 'app_module_feature_code' => 'GODOWN_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'parent' => $statutory, 'sort_order' => 50]);

        // Miscellaneous
        $misc = $this->create([
            'menu_name' => 'Miscellaneous',
            'app_module_feature_code' => 'MISCELLANEOUS_MENU_VIEW',
            'icon' => 'MichelinStar',
            'parent' => $masters,
            'sort_order' => 70,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Delivery Places', 'route' => '/masters/miscellaneous/delivery_places', 'app_module_feature_code' => 'DELIVERY_PLACES_MENU_VIEW', 'icon' => 'LocationBolt', 'parent' => $misc, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Delivery Routes', 'route' => '/masters/miscellaneous/delivery_routes', 'app_module_feature_code' => 'DELIVERY_ROUTES_MENU_VIEW', 'icon' => 'Route2', 'parent' => $misc, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Delivery Vehicles', 'route' => '/masters/miscellaneous/delivery_vehicles', 'app_module_feature_code' => 'MISCELLANEOUS_MENU_VIEW', 'icon' => 'Truck', 'parent' => $misc, 'sort_order' => 30, 'is_visible' => false]);

        // ── Administration ───────────────────────────────────────
        $admin = $this->create([
            'menu_name' => 'Administration',
            'app_module_feature_code' => 'ADMINISTRATION_MENU_VIEW',
            'icon' => 'Settings',
            'sort_order' => 40,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'User', 'route' => '/administration/user', 'app_module_feature_code' => 'USER_MENU_VIEW', 'icon' => 'Users', 'parent' => $admin, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Roles', 'route' => '/administration/role', 'app_module_feature_code' => 'ROLE_MENU_VIEW', 'icon' => 'Radar2', 'parent' => $admin, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Roles & Permissions', 'route' => '/administration/permission', 'app_module_feature_code' => 'PERMISSION_MENU_VIEW', 'icon' => 'PremiumRights', 'parent' => $admin, 'sort_order' => 30]);
        $this->create(['menu_name' => 'App Module', 'route' => '/administration/app_module', 'app_module_feature_code' => 'APP_MODULE_MENU_VIEW', 'icon' => 'Apps', 'parent' => $admin, 'sort_order' => 40]);
        $this->create(['menu_name' => 'App Features', 'route' => '/administration/app_module_feature', 'app_module_feature_code' => 'APP_FEATURE_MENU_VIEW', 'icon' => 'PaperBag', 'parent' => $admin, 'sort_order' => 50]);
        $this->create(['menu_name' => 'App Menu Features', 'route' => '/administration/Menu', 'app_module_feature_code' => 'APP_FEATURE_MENU_VIEW', 'icon' => 'ListDetails', 'parent' => $admin, 'sort_order' => 60]);

        // ── Reports ──────────────────────────────────────────────
        $reports = $this->create([
            'menu_name' => 'Reports',
            'app_module_feature_code' => 'REPORTS_MENU_VIEW',
            'icon' => 'ChartBar',
            'sort_order' => 50,
            'is_group' => true,
        ]);

        // Financial Statements
        $fin = $this->create([
            'menu_name' => 'Financial Statements',
            'app_module_feature_code' => 'BALANCE_SHEET_MENU_VIEW',
            'icon' => 'ChartBar',
            'parent' => $reports,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Balance Sheet', 'route' => '/reports/balance_sheet', 'app_module_feature_code' => 'BALANCE_SHEET_MENU_VIEW', 'icon' => 'Scale', 'parent' => $fin, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Profit & Loss', 'route' => '/reports/profit_and_loss', 'app_module_feature_code' => 'PROFIT_LOSS_MENU_VIEW', 'icon' => 'TrendingUp', 'parent' => $fin, 'sort_order' => 20]);

        // Day Book & Registers
        $daybook = $this->create([
            'menu_name' => 'Day Book & Registers',
            'app_module_feature_code' => 'DAYBOOK_MENU_VIEW',
            'icon' => 'Book',
            'parent' => $reports,
            'sort_order' => 20,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Day Book', 'route' => '/reports/day_book', 'app_module_feature_code' => 'DAYBOOK_MENU_VIEW', 'icon' => 'Notebook', 'parent' => $daybook, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Day Book (Self)', 'route' => '/reports/day_book/self', 'app_module_feature_code' => 'DAYBOOK_SELF_MENU_VIEW', 'icon' => 'UserCheck', 'parent' => $daybook, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Receipt Book', 'route' => '/reports/receipt_book', 'app_module_feature_code' => 'RECEIPTBOOK_MENU_VIEW', 'icon' => 'Receipt', 'parent' => $daybook, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Distributor Book', 'route' => '/reports/distributor_book', 'app_module_feature_code' => 'DISTRIBUTORBOOK_MENU_VIEW', 'icon' => 'TruckDelivery', 'parent' => $daybook, 'sort_order' => 40]);

        // Stock & Inventory
        $stock = $this->create([
            'menu_name' => 'Stock & Inventory',
            'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW',
            'icon' => 'Packages',
            'parent' => $reports,
            'sort_order' => 30,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Stock In Hand (Item Summary)', 'route' => '/reports/stock_summary/stock-in-hand', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'Report', 'parent' => $stock, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Stock In Hand (Godown Wise)', 'route' => '/reports/stock_summary/stock-in-hand-godown-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'parent' => $stock, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Stock In Hand (Zone Wise)', 'route' => '/reports/stock_summary/stock-in-hand-zone-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'Map', 'parent' => $stock, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Stock In Hand (Item Wise)', 'route' => '/reports/stock_summary/stock-in-hand-item-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'ListDetails', 'parent' => $stock, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Stock In Hand (Voucher Wise)', 'route' => '/reports/stock_summary/stock-in-hand-voucher-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'FileInvoice', 'parent' => $stock, 'sort_order' => 50]);
        $this->create(['menu_name' => 'Opening Entry', 'route' => '/reports/opening_entry', 'app_module_feature_code' => 'OPENING_ENTRY_REPORT_MENU_VIEW', 'icon' => 'DoorEnter', 'parent' => $stock, 'sort_order' => 60]);

        // Freight & Logistics
        $freight = $this->create([
            'menu_name' => 'Freight & Logistics',
            'app_module_feature_code' => 'FREIGHT_MENU_VIEW',
            'icon' => 'Truck',
            'parent' => $reports,
            'sort_order' => 40,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Delivery Note (Zone Wise)', 'route' => '/reports/freight/delivery-note-zone-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'MapPin', 'parent' => $freight, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Delivery Note (Godown Wise)', 'route' => '/reports/freight/delivery-note-godown-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'parent' => $freight, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Freight (Zone Wise)', 'route' => '/reports/freight/freight-zone-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'Route', 'parent' => $freight, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Freight (Transporter Wise)', 'route' => '/reports/freight/freight-transporter-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'Truck', 'parent' => $freight, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Freight (Transporter Item Wise)', 'route' => '/reports/freight/freight-transporter-item-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'Truck', 'parent' => $freight, 'sort_order' => 50]);
        $this->create(['menu_name' => 'Freight (Voucher Wise)', 'route' => '/reports/freight/freight-voucher-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'FileText', 'parent' => $freight, 'sort_order' => 60]);
        $this->create(['menu_name' => 'Freight (Godown Wise)', 'route' => '/reports/freight/freight-godown-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'parent' => $freight, 'sort_order' => 70]);

        // ── Year-End Process ─────────────────────────────────────
        $yearEnd = $this->create([
            'menu_name' => 'Year-End Process',
            'app_module_feature_code' => 'YEAR_END_PROCESS_MENU_VIEW',
            'icon' => 'Archive',
            'sort_order' => 60,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Close Fiscal Year', 'route' => '/masters/organization/fiscal_year', 'app_module_feature_code' => 'CLOSE_FISCAL_YEAR_MENU_VIEW', 'icon' => 'Archive', 'parent' => $yearEnd, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Opening Journal', 'route' => '/masters/organization/fiscal_year', 'app_module_feature_code' => 'OPENING_JOURNAL_MENU_VIEW', 'icon' => 'DoorEnter', 'parent' => $yearEnd, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Opening Entry Report', 'route' => '/reports/opening_entry', 'app_module_feature_code' => 'OPENING_ENTRY_REPORT_MENU_VIEW', 'icon' => 'Report', 'parent' => $yearEnd, 'sort_order' => 30]);

        // ── Other (hidden by default, visible via permissions) ────
        $other = $this->create([
            'menu_name' => 'Other',
            'app_module_feature_code' => 'SETTINGS_MENU_VIEW',
            'icon' => 'Settings',
            'sort_order' => 999,
            'is_group' => true,
            'is_visible' => false,
        ]);

        $settings = $this->create([
            'menu_name' => 'Settings',
            'app_module_feature_code' => 'SETTINGS_MENU_VIEW',
            'icon' => 'Settings',
            'parent' => $other,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Profile', 'route' => '/settings', 'icon' => 'UserCheck', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'parent' => $settings, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Account', 'route' => '/settings/account', 'icon' => 'Tool', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'parent' => $settings, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Appearance', 'route' => '/settings/appearance', 'icon' => 'Palette', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'parent' => $settings, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Notifications', 'route' => '/settings/notifications', 'icon' => 'Notification', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'parent' => $settings, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Display', 'route' => '/settings/display', 'icon' => 'BrowserCheck', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'parent' => $settings, 'sort_order' => 50]);

        $this->create(['menu_name' => 'Help Center', 'route' => '/help-center', 'icon' => 'Help', 'app_module_feature_code' => 'HELP_CENTER_MENU_VIEW', 'parent' => $other, 'sort_order' => 10]);

        $this->command->info('MenuSeeder: '.Menu::count().' menu entries seeded.');
    }

    /** Create a menu entry returning the model. */
    private function create(array $data): Menu
    {
        $featureCode = $data['app_module_feature_code'] ?? null;
        unset($data['app_module_feature_code']);

        $parent = $data['parent'] ?? null;
        unset($data['parent']);

        $featureId = $featureCode ? ($this->featureIds[$featureCode] ?? null) : null;
        if ($featureCode && ! $featureId) {
            $this->command->warn("Feature code '{$featureCode}' not found — menu '{$data['menu_name']}' will have no permission linkage.");
        }

        $payload = array_merge([
            'app_module_feature_id' => $featureId,
            'menu_name' => 'Unnamed',
            'route' => null,
            'icon' => null,
            'parent_id' => $parent?->id ?? null,
            'sort_order' => 0,
            'status' => 'active',
            'is_visible' => true,
            'is_group' => false,
            'description' => null,
        ], $data);

        return Menu::create($payload);
    }
}
