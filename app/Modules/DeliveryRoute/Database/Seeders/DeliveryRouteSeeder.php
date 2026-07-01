<?php

namespace Modules\DeliveryRoute\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DeliveryRoute\Models\DeliveryRoute;

class DeliveryRouteSeeder extends Seeder
{
    public function run(): void
    {
        DeliveryRoute::create(['name' => 'Sample DeliveryRoute']);

        // Uncomment to use factory if available
        // DeliveryRoute::factory()->count(10)->create();
    }
}
