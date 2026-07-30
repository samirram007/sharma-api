<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voucher_types', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_classification_id')->nullable()->after('voucher_category_id')->comment('Link to classifications');
            $table->index('voucher_classification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_types', function (Blueprint $table) {
            $table->dropForeign(['voucher_classification_id']);
            $table->dropColumn('voucher_classification_id');
        });
    }
};
