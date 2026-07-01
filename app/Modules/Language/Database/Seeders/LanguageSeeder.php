<?php

namespace Modules\Language\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Language\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::create(['name' => 'Sample Language']);

        // Uncomment to use factory if available
        // Language::factory()->count(10)->create();
    }
}
