<?php

namespace Modules\StockJournalBatchEntry\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StockJournalBatchEntry\Models\StockJournalBatchEntry;

class StockJournalBatchEntrySeeder extends Seeder
{
    public function run(): void
    {
        StockJournalBatchEntry::create(['name' => 'Sample StockJournalBatchEntry']);

        // Uncomment to use factory if available
        // StockJournalBatchEntry::factory()->count(10)->create();
    }
}
