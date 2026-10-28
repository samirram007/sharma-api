<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_dispatch_details', function (Blueprint $table) {
            $table->decimal('sender_rate', 15, 2)->nullable()->after('rate');
            $table->decimal('sender_freight_charges', 15, 2)->nullable()->after('freight_charges');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_dispatch_details', function (Blueprint $table) {
            $table->dropColumn(['sender_rate', 'sender_freight_charges']);
        });
    }
};
