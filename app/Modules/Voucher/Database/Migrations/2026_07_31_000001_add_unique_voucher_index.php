<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a unique index on (voucher_type_id, voucher_no, fiscal_year_id)
     * as a database-level safety net against duplicate voucher numbers.
     * This complements the application-level lockForUpdate() protection
     * in VoucherService and VoucherNoService.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unique(
                ['voucher_type_id', 'voucher_no', 'fiscal_year_id'],
                'uq_vouchers_type_no_fiscal'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropUnique('uq_vouchers_type_no_fiscal');
        });
    }
};
