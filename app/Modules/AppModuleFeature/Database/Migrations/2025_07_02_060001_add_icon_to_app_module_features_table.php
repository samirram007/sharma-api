<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The create_app_module_features migration (2025_10_13) has a later
        // timestamp, so on a fresh migrate this ALTER would run before the
        // table exists. Guard with hasTable (the column itself is defined in
        // the create migration).
        if (! Schema::hasTable('app_module_features')) {
            return;
        }

        Schema::table('app_module_features', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('action');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_module_features') || ! Schema::hasColumn('app_module_features', 'icon')) {
            return;
        }

        Schema::table('app_module_features', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
