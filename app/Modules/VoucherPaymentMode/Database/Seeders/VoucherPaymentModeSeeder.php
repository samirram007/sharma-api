<?php

namespace Modules\VoucherPaymentMode\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VoucherPaymentMode\Models\VoucherPaymentMode;

class VoucherPaymentModeSeeder extends Seeder
{
    public function run(): void
    {
        VoucherPaymentMode::create(['name' => 'Sample VoucherPaymentMode']);

        // Uncomment to use factory if available
        // VoucherPaymentMode::factory()->count(10)->create();
    }
}
