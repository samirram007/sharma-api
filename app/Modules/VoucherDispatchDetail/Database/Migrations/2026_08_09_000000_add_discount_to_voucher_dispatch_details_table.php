<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_dispatch_details', function (Blueprint $table) {
            // Flat ₹ discount entered in the Freight Calculator (dispatch details).
            $table->decimal('discount', 15, 2)->nullable()->after('other_charges');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_dispatch_details', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
