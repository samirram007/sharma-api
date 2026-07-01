<?php

namespace Modules\StockItemBatch\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StockItemBatch\Models\StockItemBatch;

class StockItemBatchSeeder extends Seeder
{
    public function run(): void
    {
        StockItemBatch::create(['name' => 'Sample StockItemBatch']);

        // Uncomment to use factory if available
        // StockItemBatch::factory()->count(10)->create();
    }
}
