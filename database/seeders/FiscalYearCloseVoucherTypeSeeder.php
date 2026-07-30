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
     * Creates system voucher types used by the Fiscal Year Close process.
     * These are hidden from normal voucher creation UIs.
     */
    public function run(): void
    {
        // Ensure voucher categories exist
        $journalCategory = VoucherCategory::firstOrCreate(
            ['code' => 'journal'],
            ['name' => 'Journal', 'description' => 'Journal entries', 'status' => 'active']
        );

        $stockCategory = VoucherCategory::firstOrCreate(
            ['code' => 'stock'],
            ['name' => 'Stock', 'description' => 'Stock/Inventory entries', 'status' => 'active']
        );

        // CLOSING_ACCOUNT — transfers P&L to Capital at year-end
        VoucherType::firstOrCreate(
            ['code' => 'CLOSING_ACCOUNT'],
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

        // CLOSING_STOCK — zeroes out inventory quantities at year-end
        VoucherType::firstOrCreate(
            ['code' => 'CLOSING_STOCK'],
            [
                'name' => 'Closing Stock',
                'print_name' => 'Closing Stock Voucher',
                'description' => 'System voucher: zeroes out stock inventory at fiscal year end',
                'voucher_category_id' => $stockCategory->id,
                'is_financial' => false,
                'is_effecting' => false,
                'is_hidden' => true,
                'is_system' => true,
                'status' => 'active',
            ]
        );
    }
}
