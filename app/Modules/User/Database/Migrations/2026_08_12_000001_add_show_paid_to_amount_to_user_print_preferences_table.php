<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_print_preferences', function (Blueprint $table) {
            $table->boolean('show_paid_to_amount')->default(true)->after('show_fare_details');
        });
    }

    public function down(): void
    {
        Schema::table('user_print_preferences', function (Blueprint $table) {
            $table->dropColumn('show_paid_to_amount');
        });
    }
};
