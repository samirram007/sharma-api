<?php

namespace Modules\StockJournalGodownEntryPurge\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StockJournalGodownEntryPurge\Models\StockJournalGodownEntryPurge;

class StockJournalGodownEntryPurgeSeeder extends Seeder
{
    public function run(): void
    {
        StockJournalGodownEntryPurge::create(['name' => 'Sample StockJournalGodownEntryPurge']);

        // Uncomment to use factory if available
        // StockJournalGodownEntryPurge::factory()->count(10)->create();
    }
}
