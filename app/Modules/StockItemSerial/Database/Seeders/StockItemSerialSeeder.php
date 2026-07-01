<?php

namespace Modules\StockItemSerial\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StockItemSerial\Models\StockItemSerial;

class StockItemSerialSeeder extends Seeder
{
    public function run(): void
    {
        StockItemSerial::create(['name' => 'Sample StockItemSerial']);

        // Uncomment to use factory if available
        // StockItemSerial::factory()->count(10)->create();
    }
}
