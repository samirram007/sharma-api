<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * delivery_places was created without a `status` column, but the Model,
 * Resource, and frontend schema all expect one (active/inactive). Existing
 * rows get their status derived from the `is_active` boolean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_places', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_places', 'status')) {
                $table->string('status')->default('active')->after('is_active');
            }
        });

        // Backfill from the legacy is_active boolean.
        DB::table('delivery_places')->where('is_active', false)->update(['status' => 'inactive']);
        DB::table('delivery_places')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void
    {
        // Intentionally left empty — dropping the column would destroy data.
    }
};
