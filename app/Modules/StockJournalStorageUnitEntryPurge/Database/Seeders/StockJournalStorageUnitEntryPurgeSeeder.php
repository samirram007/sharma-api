<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\StockJournalStorageUnitEntryPurge\Models\StockJournalStorageUnitEntryPurge;

class StockJournalStorageUnitEntryPurgeSeeder extends Seeder
{
    public function run(): void
    {
        StockJournalStorageUnitEntryPurge::create(['name' => 'Sample StockJournalStorageUnitEntryPurge']);

        // Uncomment to use factory if available
        // StockJournalStorageUnitEntryPurge::factory()->count(10)->create();
    }
}
