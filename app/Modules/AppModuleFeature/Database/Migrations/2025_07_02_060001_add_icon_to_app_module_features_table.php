<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_module_features', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('app_module_features', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
