<?php

namespace Modules\DayBook\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DayBook\Models\DayBook;

class DayBookSeeder extends Seeder
{
    public function run(): void
    {
        DayBook::create(['name' => 'Sample DayBook']);

        // Uncomment to use factory if available
        // DayBook::factory()->count(10)->create();
    }
}
