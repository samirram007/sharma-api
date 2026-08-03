<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transporter_id');
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_number');
            $table->string('capacity')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();
            // Note: index name is prefixed with 'delivery_' because the Vehicle
            // module migration already uses 'unique_transporter_vehicle' and
            // SQLite requires index names to be unique database-wide.
            $table->unique(['transporter_id', 'vehicle_number'], 'unique_delivery_transporter_vehicle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_vehicles');
    }
};
