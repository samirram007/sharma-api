<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create(['name' => 'Sample Setting']);

        // Uncomment to use factory if available
        // Setting::factory()->count(10)->create();
    }
}
