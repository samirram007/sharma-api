<?php

namespace Modules\DeliveryVehicle\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DeliveryVehicle\Models\DeliveryVehicle;

class DeliveryVehicleSeeder extends Seeder
{
    public function run(): void
    {
        DeliveryVehicle::create(['name' => 'Sample DeliveryVehicle']);

        // Uncomment to use factory if available
        // DeliveryVehicle::factory()->count(10)->create();
    }
}
