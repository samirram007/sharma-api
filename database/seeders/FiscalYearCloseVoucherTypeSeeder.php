<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherType\Models\VoucherType;

class FiscalYearCloseVoucherTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the system voucher types used by the Fiscal Year Close / Open
     * workflow. Idempotent and self-contained, so it can also be run standalone
     * (`php artisan db:seed --class=FiscalYearCloseVoucherTypeSeeder`) on
     * databases where the full module VoucherTypeSeeder has not run.
     *
     * Codes match what FiscalYearCloseService / FiscalYearOpenService look up:
     * CLSAC, CLSSK and OPNJL (the unified opening voucher). These are hidden
     * from normal voucher creation UIs.
     *
     * Note: the module `VoucherTypeSeeder` (wired into `DatabaseSeeder`) is the
     * primary path and its system types use explicit IDs. If both seeders must
     * run on the same database, run the module seeder FIRST — running this
     * standalone seeder first and the full seeder afterwards would collide on
     * the unique `voucher_types.code` column.
     */
    public function run(): void
    {
        // Reuse the same categories the module VoucherCategorySeeder creates,
        // so running both seeders does not produce duplicate categories.
        $journalCategory = VoucherCategory::firstOrCreate(
            ['code' => 'ACC'],
            ['name' => 'Accounting', 'description' => 'Category for financial transactions, adjustments, and cash flow management', 'status' => 'active']
        );

        $stockCategory = VoucherCategory::firstOrCreate(
            ['code' => 'INV'],
            ['name' => 'Inventory', 'description' => 'Category for stock movements, manufacturing, and physical stock adjustments', 'status' => 'active']
        );

        // CLSAC — closes P&L ledgers to Capital at year-end
        VoucherType::firstOrCreate(
            ['code' => 'CLSAC'],
            [
                'name' => 'Closing Account',
                'print_name' => 'Closing Account Voucher',
                'description' => 'System voucher: transfers profit/loss to capital account at fiscal year end',
                'voucher_category_id' => $journalCategory->id,
                'is_financial' => true,
                'is_effecting' => false,
                'is_hidden' => true,
                'is_system' => true,
                'status' => 'active',
            ]
        );

        // CLSSK — freezes stock quantities at year-end
        VoucherType::firstOrCreate(
            ['code' => 'CLSSK'],
            [
                'name' => 'Closing Stock',
                'print_name' => 'Closing Stock Voucher',
                'description' => 'System voucher: freezes stock inventory at fiscal year end',
                'voucher_category_id' => $stockCategory->id,
                'is_financial' => false,
                'is_effecting' => false,
                'is_hidden' => true,
                'is_system' => true,
                'status' => 'active',
            ]
        );

        // OPNJL — unified opening entry carrying forward balances + stock
        VoucherType::firstOrCreate(
            ['code' => 'OPNJL'],
            [
                'name' => 'OpeningJournal',
                'print_name' => 'Opening Journal Voucher',
                'description' => 'System voucher: unified opening entry carrying forward account balances and stock quantities into the new fiscal year',
                'voucher_category_id' => $journalCategory->id,
                'is_financial' => true,
                'is_effecting' => true,
                'is_hidden' => true,
                'is_system' => true,
                'status' => 'active',
            ]
        );
    }
}
