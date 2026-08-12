<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('menu', 'is_top_menu')) {
            return;
        }
        Schema::table('menu', function (Blueprint $table) {
            $table->boolean('is_top_menu')->default(false)->after('is_group');
        });
    }

    public function down(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            $table->dropColumn('is_top_menu');
        });
    }
};
