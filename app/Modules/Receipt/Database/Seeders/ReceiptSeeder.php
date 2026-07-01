<?php

namespace Modules\Receipt\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Receipt\Models\Receipt;

class ReceiptSeeder extends Seeder
{
    public function run(): void
    {
        Receipt::create(['name' => 'Sample Receipt']);

        // Uncomment to use factory if available
        // Receipt::factory()->count(10)->create();
    }
}
