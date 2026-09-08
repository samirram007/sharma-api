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
            'description' => 'Top-level group for general and landing pages.',
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create([
            'menu_name' => 'Dashboard',
            'route' => '/',
            'app_module_feature_code' => 'DASHBOARD_MENU_VIEW',
            'icon' => 'LayoutDashboard',
            'description' => 'Home dashboard with quick summaries, charts and shortcuts.',
            'parent' => $general,
            'sort_order' => 10,
            'is_top_menu' => true,
        ]);

        // ── Transactions ─────────────────────────────────────────
        $transactions = $this->create([
            'menu_name' => 'Transactions',
            'app_module_feature_code' => 'TRANSACTION_MENU_VIEW',
            'icon' => 'Book',
            'description' => 'Top-level group for entering day-to-day business transactions.',
            'sort_order' => 20,
            'is_group' => true,
        ]);

        $accountsInTrans = $this->create([
            'menu_name' => 'Accounts',
            'app_module_feature_code' => 'ACCOUNTS_MENU_VIEW',
            'icon' => 'Book',
            'description' => 'Group for voucher entry and book-keeping screens.',
            'parent' => $transactions,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create([
            'menu_name' => 'Vouchers',
            'route' => '/transactions/vouchers',
            'app_module_feature_code' => 'VOUCHERS_MENU_VIEW',
            'icon' => 'ClipboardList',
            'description' => 'List, create and manage all financial vouchers.',
            'parent' => $accountsInTrans,
            'sort_order' => 10,
        ]);

        // Voucher types directly under Transactions (each gated by its own menu-view permission)
        $this->create(['menu_name' => 'Received (GRN)', 'route' => '/transactions/vouchers/receipt_note', 'app_module_feature_code' => 'RECEIPT_NOTE_MENU_VIEW', 'icon' => 'TruckDelivery', 'description' => 'Record goods received from suppliers (goods receipt note).', 'parent' => $transactions, 'sort_order' => 20, 'is_top_menu' => true]);
        $this->create(['menu_name' => 'Delivery Note', 'route' => '/transactions/vouchers/delivery_note', 'app_module_feature_code' => 'DELIVERY_NOTE_MENU_VIEW', 'icon' => 'Truck', 'description' => 'Record goods dispatched to customers (delivery note).', 'parent' => $transactions, 'sort_order' => 30, 'is_top_menu' => true]);
        $this->create(['menu_name' => 'Conversion', 'route' => '/transactions/vouchers/conversion_journal', 'app_module_feature_code' => 'CONVERSION_MENU_VIEW', 'icon' => 'ClipboardType', 'description' => 'Convert stock from one item into another with a manufacturing journal.', 'parent' => $transactions, 'sort_order' => 40, 'is_top_menu' => true]);
        $this->create(['menu_name' => 'Physical Stock', 'route' => '/transactions/vouchers/physical_stock', 'app_module_feature_code' => 'PHYSICAL_STOCK_MENU_VIEW', 'icon' => 'Checklist', 'description' => 'Enter counted physical stock and post quantity adjustments.', 'parent' => $transactions, 'sort_order' => 50]);
        $this->create(['menu_name' => 'Opening Stock', 'route' => '/transactions/vouchers/opening_stock', 'app_module_feature_code' => 'OPENING_STOCK_MENU_VIEW', 'icon' => 'PackageOpen', 'description' => 'Set the opening stock quantities for items.', 'parent' => $transactions, 'sort_order' => 60]);
        $this->create(['menu_name' => 'Freight', 'route' => '/transactions/freight', 'app_module_feature_code' => 'FREIGHT_MENU_VIEW', 'icon' => 'TruckDelivery', 'description' => 'Record and manage freight (goods transport) transactions.', 'parent' => $transactions, 'sort_order' => 70, 'is_top_menu' => true]);

        $this->create([
            'menu_name' => 'Day Book',
            'route' => '/reports/day_book',
            'app_module_feature_code' => 'DAYBOOK_MENU_VIEW',
            'icon' => 'Book',
            'description' => 'Open the chronological day-wise register of all transactions.',
            'parent' => $transactions,
            'sort_order' => 80,
        ]);

        // ── Masters ──────────────────────────────────────────────
        $masters = $this->create([
            'menu_name' => 'Masters',
            'app_module_feature_code' => 'MASTER_MENU_VIEW',
            'icon' => 'NotebookTabs',
            'description' => 'Top-level group for maintaining business master data.',
            'sort_order' => 30,
            'is_group' => true,
        ]);

        // Organization
        $org = $this->create([
            'menu_name' => 'Organization',
            'app_module_feature_code' => 'ORGANIZATION_MENU_VIEW',
            'icon' => 'Building2',
            'description' => 'Group for company-wide organisation settings.',
            'parent' => $masters,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Company', 'route' => '/masters/organization/company', 'app_module_feature_code' => 'COMPANY_MENU_VIEW', 'icon' => 'Building2', 'description' => 'Maintain the company profile and business details.', 'parent' => $org, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Fiscal Year', 'route' => '/masters/organization/fiscal_year', 'app_module_feature_code' => 'FISCAL_YEAR_MENU_VIEW', 'icon' => 'ClipboardList', 'description' => 'Create and manage fiscal year periods.', 'parent' => $org, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Currency', 'route' => '/masters/organization/currency', 'app_module_feature_code' => 'CURRENCY_MENU_VIEW', 'icon' => 'Coin', 'description' => 'Maintain the currencies used across the application.', 'parent' => $org, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Country', 'route' => '/masters/organization/country', 'app_module_feature_code' => 'COUNTRY_MENU_VIEW', 'icon' => 'Map', 'description' => 'Maintain the countries used for addresses.', 'parent' => $org, 'sort_order' => 40]);
        $this->create(['menu_name' => 'State', 'route' => '/masters/organization/state', 'app_module_feature_code' => 'STATE_MENU_VIEW', 'icon' => 'MapPin', 'description' => 'Maintain the states for the supported countries.', 'parent' => $org, 'sort_order' => 50]);

        // Accounts
        $accts = $this->create([
            'menu_name' => 'Accounts',
            'app_module_feature_code' => 'ACCOUNTS_MENU_VIEW',
            'icon' => 'NotebookTabs',
            'description' => 'Group for chart of accounts and voucher type masters.',
            'parent' => $masters,
            'sort_order' => 20,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Chart of Accounts', 'route' => '/masters/accounts/account_group', 'app_module_feature_code' => 'CHART_OF_ACCOUNTS_MENU_VIEW', 'icon' => 'ListDetails', 'description' => 'Maintain the hierarchical chart of account groups.', 'parent' => $accts, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Account Ledger', 'route' => '/masters/accounts/account_ledger', 'app_module_feature_code' => 'ACCOUNT_LEDGER_MENU_VIEW', 'icon' => 'Notebook', 'description' => 'Maintain ledger accounts under account groups.', 'parent' => $accts, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Voucher Type', 'route' => '/masters/accounts/voucher_type', 'app_module_feature_code' => 'VOUCHER_TYPE_MENU_VIEW', 'icon' => 'Receipt', 'description' => 'Configure the voucher types available to transactions.', 'parent' => $accts, 'sort_order' => 30]);

        // Party
        $party = $this->create([
            'menu_name' => 'Party',
            'app_module_feature_code' => 'PARTY_MENU_VIEW',
            'icon' => 'Users',
            'description' => 'Group for business party masters (distributors, suppliers, transporters).',
            'parent' => $masters,
            'sort_order' => 30,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Distributor', 'route' => '/masters/party/distributor', 'app_module_feature_code' => 'DISTRIBUTOR_MENU_VIEW', 'icon' => 'TruckDelivery', 'description' => 'Maintain distributor master records.', 'parent' => $party, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Supplier', 'route' => '/masters/party/supplier', 'app_module_feature_code' => 'SUPPLIER_MENU_VIEW', 'icon' => 'Truck', 'description' => 'Maintain supplier master records.', 'parent' => $party, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Transporter', 'route' => '/masters/party/transporter', 'app_module_feature_code' => 'TRANSPORTER_MENU_VIEW', 'icon' => 'Route', 'description' => 'Maintain transporter master records.', 'parent' => $party, 'sort_order' => 30]);

        // Inventory
        $inv = $this->create([
            'menu_name' => 'Inventory',
            'app_module_feature_code' => 'INVENTORY_MENU_VIEW',
            'icon' => 'WarehouseIcon',
            'description' => 'Group for inventory (stock) master data.',
            'parent' => $masters,
            'sort_order' => 40,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Stock Item', 'route' => '/masters/inventory/stock_item', 'app_module_feature_code' => 'STOCK_ITEM_MENU_VIEW', 'icon' => 'Packages', 'description' => 'Maintain stock item master records.', 'parent' => $inv, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Stock Group', 'route' => '/masters/inventory/stock_group', 'app_module_feature_code' => 'STOCK_GROUP_MENU_VIEW', 'icon' => 'ListDetails', 'description' => 'Maintain stock groups used to organise items.', 'parent' => $inv, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Stock Category', 'route' => '/masters/inventory/stock_category', 'app_module_feature_code' => 'STOCK_CATEGORY_MENU_VIEW', 'icon' => 'Checklist', 'description' => 'Maintain stock category master records.', 'parent' => $inv, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Stock Unit', 'route' => '/masters/inventory/stock_unit', 'app_module_feature_code' => 'STOCK_UNIT_MENU_VIEW', 'icon' => 'Scale', 'description' => 'Maintain the units of measure used for stock.', 'parent' => $inv, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Godown', 'route' => '/masters/inventory/godown', 'app_module_feature_code' => 'GODOWN_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'description' => 'Maintain godowns (warehouses and storage locations).', 'parent' => $inv, 'sort_order' => 50]);

        // Payroll
        $payroll = $this->create([
            'menu_name' => 'Payroll',
            'app_module_feature_code' => 'PAYROLL_MENU_VIEW',
            'icon' => 'HandCoinsIcon',
            'description' => 'Group for employee and payroll related masters.',
            'parent' => $masters,
            'sort_order' => 50,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Employee', 'route' => '/masters/payroll/employee', 'app_module_feature_code' => 'EMPLOYEE_MENU_VIEW', 'icon' => 'Users', 'description' => 'Maintain employee master records.', 'parent' => $payroll, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Department', 'route' => '/masters/payroll/department', 'app_module_feature_code' => 'DEPARTMENT_MENU_VIEW', 'icon' => 'Building2', 'description' => 'Maintain department master records.', 'parent' => $payroll, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Designation', 'route' => '/masters/payroll/designation', 'app_module_feature_code' => 'DESIGNATION_MENU_VIEW', 'icon' => 'UserCheck', 'description' => 'Maintain job designation master records.', 'parent' => $payroll, 'sort_order' => 30]);

        // Statutory
        $statutory = $this->create([
            'menu_name' => 'Statutory',
            'app_module_feature_code' => 'STATUTORY_MENU_VIEW',
            'icon' => 'LandmarkIcon',
            'description' => 'Group exposing inventory masters under the statutory classification.',
            'parent' => $masters,
            'sort_order' => 60,
            'is_group' => true,
        ]);

        // Statutory items reuse same routes as Inventory but under Statutory group
        $this->create(['menu_name' => 'Stock Item', 'route' => '/masters/inventory/stock_item', 'app_module_feature_code' => 'STOCK_ITEM_MENU_VIEW', 'icon' => 'Notebook', 'description' => 'Manage stock item masters (statutory view of the same Inventory screen).', 'parent' => $statutory, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Stock Group', 'route' => '/masters/inventory/stock_group', 'app_module_feature_code' => 'STOCK_GROUP_MENU_VIEW', 'icon' => 'ListDetails', 'description' => 'Manage stock groups (statutory view of the same Inventory screen).', 'parent' => $statutory, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Stock Category', 'route' => '/masters/inventory/stock_category', 'app_module_feature_code' => 'STOCK_CATEGORY_MENU_VIEW', 'icon' => 'Checklist', 'description' => 'Manage stock categories (statutory view of the same Inventory screen).', 'parent' => $statutory, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Stock Unit', 'route' => '/masters/inventory/stock_unit', 'app_module_feature_code' => 'STOCK_UNIT_MENU_VIEW', 'icon' => 'Scale', 'description' => 'Manage stock units (statutory view of the same Inventory screen).', 'parent' => $statutory, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Godown', 'route' => '/masters/inventory/godown', 'app_module_feature_code' => 'GODOWN_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'description' => 'Manage godowns (statutory view of the same Inventory screen).', 'parent' => $statutory, 'sort_order' => 50]);

        // Miscellaneous
        $misc = $this->create([
            'menu_name' => 'Miscellaneous',
            'app_module_feature_code' => 'MISCELLANEOUS_MENU_VIEW',
            'icon' => 'MichelinStar',
            'description' => 'Group for delivery related configuration masters.',
            'parent' => $masters,
            'sort_order' => 70,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Delivery Places', 'route' => '/masters/miscellaneous/delivery_places', 'app_module_feature_code' => 'DELIVERY_PLACES_MENU_VIEW', 'icon' => 'LocationBolt', 'description' => 'Maintain delivery place master records.', 'parent' => $misc, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Delivery Routes', 'route' => '/masters/miscellaneous/delivery_routes', 'app_module_feature_code' => 'DELIVERY_ROUTES_MENU_VIEW', 'icon' => 'Route2', 'description' => 'Maintain delivery route master records.', 'parent' => $misc, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Delivery Vehicles', 'route' => '/masters/miscellaneous/delivery_vehicles', 'app_module_feature_code' => 'MISCELLANEOUS_MENU_VIEW', 'icon' => 'Truck', 'description' => 'Maintain delivery vehicle masters (currently hidden from the sidebar).', 'parent' => $misc, 'sort_order' => 30, 'is_visible' => false]);

        // ── Administration ───────────────────────────────────────
        $admin = $this->create([
            'menu_name' => 'Administration',
            'app_module_feature_code' => 'ADMINISTRATION_MENU_VIEW',
            'icon' => 'Settings',
            'description' => 'Top-level group for system administration and configuration.',
            'sort_order' => 40,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'User', 'route' => '/administration/user', 'app_module_feature_code' => 'USER_MENU_VIEW', 'icon' => 'Users', 'description' => 'Manage application users and their login access.', 'parent' => $admin, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Roles', 'route' => '/administration/role', 'app_module_feature_code' => 'ROLE_MENU_VIEW', 'icon' => 'Radar2', 'description' => 'Create and manage the roles used for access control.', 'parent' => $admin, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Roles & Permissions', 'route' => '/administration/permission', 'app_module_feature_code' => 'PERMISSION_MENU_VIEW', 'icon' => 'PremiumRights', 'description' => 'Assign module and feature permissions to roles.', 'parent' => $admin, 'sort_order' => 30]);
        $this->create(['menu_name' => 'App Module', 'route' => '/administration/app_module', 'app_module_feature_code' => 'APP_MODULE_MENU_VIEW', 'icon' => 'Apps', 'description' => 'Manage the application modules used for permissions.', 'parent' => $admin, 'sort_order' => 40]);
        $this->create(['menu_name' => 'App Features', 'route' => '/administration/app_module_feature', 'app_module_feature_code' => 'APP_FEATURE_MENU_VIEW', 'icon' => 'PaperBag', 'description' => 'Manage the features belonging to each app module.', 'parent' => $admin, 'sort_order' => 50]);
        $this->create(['menu_name' => 'App Menu Features', 'route' => '/administration/Menu', 'app_module_feature_code' => 'APP_FEATURE_MENU_VIEW', 'icon' => 'ListDetails', 'description' => 'Manage menu entries, their hierarchy and navigation flags (this page).', 'parent' => $admin, 'sort_order' => 60]);

        // ── Reports ──────────────────────────────────────────────
        // The Reports group is itself a top-nav entry: it renders as a header
        // dropdown whose children (Financial Statements, Day Book, Freight
        // Reports, …) come from this same tree.
        $reports = $this->create([
            'menu_name' => 'Reports',
            'app_module_feature_code' => 'REPORTS_MENU_VIEW',
            'icon' => 'ChartBar',
            'description' => 'Top-level group for reports and analytics.',
            'sort_order' => 50,
            'is_group' => true,
            'is_top_menu' => true,
        ]);

        // Financial Statements
        $fin = $this->create([
            'menu_name' => 'Financial Statements',
            'app_module_feature_code' => 'BALANCE_SHEET_MENU_VIEW',
            'icon' => 'ChartBar',
            'description' => 'Group for statutory financial statement reports.',
            'parent' => $reports,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Balance Sheet', 'route' => '/reports/balance_sheet', 'app_module_feature_code' => 'BALANCE_SHEET_MENU_VIEW', 'icon' => 'Scale', 'description' => 'Generate the balance sheet for a selected period.', 'parent' => $fin, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Profit & Loss', 'route' => '/reports/profit_and_loss', 'app_module_feature_code' => 'PROFIT_LOSS_MENU_VIEW', 'icon' => 'TrendingUp', 'description' => 'Generate the profit and loss statement for a selected period.', 'parent' => $fin, 'sort_order' => 20]);

        // Day Book & Registers
        $daybook = $this->create([
            'menu_name' => 'Day Book & Registers',
            'app_module_feature_code' => 'DAYBOOK_MENU_VIEW',
            'icon' => 'Book',
            'description' => 'Group for day book and transaction register reports.',
            'parent' => $reports,
            'sort_order' => 20,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Day Book', 'route' => '/reports/day_book', 'app_module_feature_code' => 'DAYBOOK_MENU_VIEW', 'icon' => 'Notebook', 'description' => 'Chronological register of all transactions for a date range.', 'parent' => $daybook, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Day Book (Self)', 'route' => '/reports/day_book/self', 'app_module_feature_code' => 'DAYBOOK_SELF_MENU_VIEW', 'icon' => 'UserCheck', 'description' => 'Register of transactions entered by you only.', 'parent' => $daybook, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Receipt Book', 'route' => '/reports/receipt_book', 'app_module_feature_code' => 'RECEIPTBOOK_MENU_VIEW', 'icon' => 'Receipt', 'description' => 'Register of cash and bank receipts for a period.', 'parent' => $daybook, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Distributor Book', 'route' => '/reports/distributor_book', 'app_module_feature_code' => 'DISTRIBUTORBOOK_MENU_VIEW', 'icon' => 'TruckDelivery', 'description' => 'Register of sales grouped by distributor for a period.', 'parent' => $daybook, 'sort_order' => 40]);

        // Stock & Inventory
        $stock = $this->create([
            'menu_name' => 'Stock & Inventory',
            'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW',
            'icon' => 'Packages',
            'description' => 'Group for stock position (stock in hand) reports.',
            'parent' => $reports,
            'sort_order' => 30,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Stock In Hand (Item Summary)', 'route' => '/reports/stock_summary/stock-in-hand', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'Report', 'description' => 'Current stock position summarised per item.', 'parent' => $stock, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Stock In Hand (Godown Wise)', 'route' => '/reports/stock_summary/stock-in-hand-godown-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'description' => 'Current stock position broken down by godown.', 'parent' => $stock, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Stock In Hand (Zone Wise)', 'route' => '/reports/stock_summary/stock-in-hand-zone-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'Map', 'description' => 'Current stock position broken down by zone.', 'parent' => $stock, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Stock In Hand (Item Wise)', 'route' => '/reports/stock_summary/stock-in-hand-item-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'ListDetails', 'description' => 'Detailed stock position listed item by item.', 'parent' => $stock, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Stock In Hand (Voucher Wise)', 'route' => '/reports/stock_summary/stock-in-hand-voucher-wise', 'app_module_feature_code' => 'STOCKSUMMARY_MENU_VIEW', 'icon' => 'FileInvoice', 'description' => 'Stock position traced back through the vouchers that changed it.', 'parent' => $stock, 'sort_order' => 50]);
        $this->create(['menu_name' => 'Opening Entry', 'route' => '/reports/opening_entry', 'app_module_feature_code' => 'OPENING_ENTRY_REPORT_MENU_VIEW', 'icon' => 'DoorEnter', 'description' => 'Report of the opening balance entries recorded.', 'parent' => $stock, 'sort_order' => 60]);

        // Receipt Note / Conversion / Manufacturing report leaves (directly under Reports,
        // each gated by its own report-view feature for role-based access)
        $this->create(['menu_name' => 'Receipt Note Report', 'route' => '/reports/receipt_note_report', 'app_module_feature_code' => 'RECEIPT_NOTE_REPORT_MENU_VIEW', 'icon' => 'FileText', 'description' => 'Report of goods receipt (GRN) transactions.', 'parent' => $reports, 'sort_order' => 41]);
        // (Deliberately NOT a top menu: it lives inside the Reports dropdown
        // via the group above.)
        $this->create(['menu_name' => 'Conversion Journal Report', 'route' => '/reports/conversion_journal_report', 'app_module_feature_code' => 'CONVERSION_JOURNAL_REPORT_MENU_VIEW', 'icon' => 'BuildingFactory', 'description' => 'Report of conversion journal vouchers.', 'parent' => $reports, 'sort_order' => 42]);
        $this->create(['menu_name' => 'Manufacturing Journal Report', 'route' => '/reports/manufacturing_journal_report', 'app_module_feature_code' => 'MANUFACTURING_JOURNAL_REPORT_MENU_VIEW', 'icon' => 'BuildingFactory', 'description' => 'Report of manufacturing journal vouchers.', 'parent' => $reports, 'sort_order' => 43]);

        // Delivery Note Reports (gated by DELIVERY_NOTE_REPORT_MENU_VIEW so a
        // Delivery Note Employee role sees only its own reports)
        $deliveryNoteReports = $this->create([
            'menu_name' => 'Delivery Note Reports',
            'app_module_feature_code' => 'DELIVERY_NOTE_REPORT_MENU_VIEW',
            'icon' => 'Truck',
            'description' => 'Group for delivery note related reports.',
            'parent' => $reports,
            'sort_order' => 50,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Delivery Note (Zone Wise)', 'route' => '/reports/freight/delivery-note-zone-wise', 'app_module_feature_code' => 'DELIVERY_NOTE_REPORT_MENU_VIEW', 'icon' => 'MapPin', 'description' => 'Delivery note report grouped by zone.', 'parent' => $deliveryNoteReports, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Delivery Note (Godown Wise)', 'route' => '/reports/freight/delivery-note-godown-wise', 'app_module_feature_code' => 'DELIVERY_NOTE_REPORT_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'description' => 'Delivery note report grouped by godown.', 'parent' => $deliveryNoteReports, 'sort_order' => 20]);

        // Freight Reports (gated by FREIGHT_REPORT_MENU_VIEW)
        $freightReports = $this->create([
            'menu_name' => 'Freight Reports',
            'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW',
            'icon' => 'Truck',
            'description' => 'Group for freight related reports.',
            'parent' => $reports,
            'sort_order' => 60,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Freight (Zone Wise)', 'route' => '/reports/freight/freight-zone-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'Route', 'description' => 'Freight report grouped by zone.', 'parent' => $freightReports, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Freight (Transporter Wise)', 'route' => '/reports/freight/freight-transporter-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'Truck', 'description' => 'Freight report grouped by transporter.', 'parent' => $freightReports, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Freight (Transporter Item Wise)', 'route' => '/reports/freight/freight-transporter-item-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'Truck', 'description' => 'Freight report grouped by transporter and item.', 'parent' => $freightReports, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Freight (Voucher Wise)', 'route' => '/reports/freight/freight-voucher-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'FileText', 'description' => 'Freight report grouped by voucher.', 'parent' => $freightReports, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Freight (Godown Wise)', 'route' => '/reports/freight/freight-godown-wise', 'app_module_feature_code' => 'FREIGHT_REPORT_MENU_VIEW', 'icon' => 'BuildingWarehouse', 'description' => 'Freight report grouped by godown.', 'parent' => $freightReports, 'sort_order' => 50]);

        // Opening Stock Reports (gated by OPENING_STOCK_REPORT_MENU_VIEW so an
        // Opening Stock Employee role sees only its own reports/setup pages)
        $openingStockReports = $this->create([
            'menu_name' => 'Opening Stock Reports',
            'app_module_feature_code' => 'OPENING_STOCK_REPORT_MENU_VIEW',
            'icon' => 'DoorEnter',
            'description' => 'Group for opening stock and opening balance reports.',
            'parent' => $reports,
            'sort_order' => 70,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Opening Entry Report', 'route' => '/reports/opening_entry', 'app_module_feature_code' => 'OPENING_ENTRY_REPORT_MENU_VIEW', 'icon' => 'DoorEnter', 'description' => 'Report of the opening balance entries recorded.', 'parent' => $openingStockReports, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Opening Balance Setup', 'route' => '/transactions/opening-balance', 'app_module_feature_code' => 'OPENING_BALANCE_MENU_VIEW', 'icon' => 'Scale', 'description' => 'Enter opening balances for ledgers and stock.', 'parent' => $openingStockReports, 'sort_order' => 20]);

        // ── Year-End Process ─────────────────────────────────────
        $yearEnd = $this->create([
            'menu_name' => 'Year-End Process',
            'app_module_feature_code' => 'YEAR_END_PROCESS_MENU_VIEW',
            'icon' => 'Archive',
            'description' => 'Top-level group for closing a fiscal year and starting the next.',
            'sort_order' => 60,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Close Fiscal Year', 'route' => '/masters/organization/fiscal_year/close', 'app_module_feature_code' => 'CLOSE_FISCAL_YEAR_MENU_VIEW', 'icon' => 'Archive', 'description' => 'Close the current fiscal year and roll balances into the next.', 'parent' => $yearEnd, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Opening Journal', 'route' => '/masters/organization/fiscal_year/new/open', 'app_module_feature_code' => 'OPENING_JOURNAL_MENU_VIEW', 'icon' => 'DoorEnter', 'description' => 'Post opening journal entries for the new fiscal year.', 'parent' => $yearEnd, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Opening Balance Setup', 'route' => '/transactions/opening-balance', 'app_module_feature_code' => 'OPENING_BALANCE_MENU_VIEW', 'icon' => 'Scale', 'description' => 'Enter opening balances for ledgers and stock in the new fiscal year.', 'parent' => $yearEnd, 'sort_order' => 25]);
        $this->create(['menu_name' => 'Opening Entry Report', 'route' => '/reports/opening_entry', 'app_module_feature_code' => 'OPENING_ENTRY_REPORT_MENU_VIEW', 'icon' => 'Report', 'description' => 'Report of opening entries posted for the new fiscal year.', 'parent' => $yearEnd, 'sort_order' => 30]);

        // ── Other (hidden by default, visible via permissions) ────
        $other = $this->create([
            'menu_name' => 'Other',
            'app_module_feature_code' => 'SETTINGS_MENU_VIEW',
            'icon' => 'Settings',
            'description' => 'Hidden group holding settings and support pages (shown only to users granted access).',
            'sort_order' => 999,
            'is_group' => true,
            'is_visible' => false,
        ]);

        $settings = $this->create([
            'menu_name' => 'Settings',
            'app_module_feature_code' => 'SETTINGS_MENU_VIEW',
            'icon' => 'Settings',
            'description' => 'Group for personal profile, account and preference settings.',
            'parent' => $other,
            'sort_order' => 10,
            'is_group' => true,
        ]);

        $this->create(['menu_name' => 'Profile', 'route' => '/settings', 'icon' => 'UserCheck', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'description' => 'View and edit your user profile.', 'parent' => $settings, 'sort_order' => 10]);
        $this->create(['menu_name' => 'Account', 'route' => '/settings/account', 'icon' => 'Tool', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'description' => 'Manage your account and security settings.', 'parent' => $settings, 'sort_order' => 20]);
        $this->create(['menu_name' => 'Appearance', 'route' => '/settings/appearance', 'icon' => 'Palette', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'description' => 'Customise the application theme and appearance.', 'parent' => $settings, 'sort_order' => 30]);
        $this->create(['menu_name' => 'Notifications', 'route' => '/settings/notifications', 'icon' => 'Notification', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'description' => 'Control which notifications you receive.', 'parent' => $settings, 'sort_order' => 40]);
        $this->create(['menu_name' => 'Display', 'route' => '/settings/display', 'icon' => 'BrowserCheck', 'app_module_feature_code' => 'SETTINGS_MENU_VIEW', 'description' => 'Adjust display and layout preferences.', 'parent' => $settings, 'sort_order' => 50]);

        $this->create(['menu_name' => 'Help Center', 'route' => '/help-center', 'icon' => 'Help', 'app_module_feature_code' => 'HELP_CENTER_MENU_VIEW', 'description' => 'Open help, documentation and support resources.', 'parent' => $other, 'sort_order' => 10]);

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
