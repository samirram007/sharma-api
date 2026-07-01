<?php

namespace Modules\StockItemGodown\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StockItemGodown\Models\StockItemGodown;

class StockItemGodownSeeder extends Seeder
{
    public function run(): void
    {
        StockItemGodown::create(['name' => 'Sample StockItemGodown']);

        // Uncomment to use factory if available
        // StockItemGodown::factory()->count(10)->create();
    }
}
