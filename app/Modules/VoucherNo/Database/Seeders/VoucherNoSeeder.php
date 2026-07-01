<?php

namespace Modules\VoucherNo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VoucherNo\Models\VoucherNo;

class VoucherNoSeeder extends Seeder
{
    public function run(): void
    {
        VoucherNo::create(['name' => 'Sample VoucherNo']);

        // Uncomment to use factory if available
        // VoucherNo::factory()->count(10)->create();
    }
}
