<?php

namespace Modules\AppMaintenance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppMaintenance\Models\AppMaintenance;

class AppMaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        AppMaintenance::create(['name' => 'Sample AppMaintenance']);

        // Uncomment to use factory if available
        // AppMaintenance::factory()->count(10)->create();
    }
}
