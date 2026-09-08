<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. voucher_nos: drop the unique index that includes branch_id, drop the
        //    column, then re-create the unique index over the remaining columns
        if (Schema::hasColumn('voucher_nos', 'branch_id')) {
            Schema::table('voucher_nos', function (Blueprint $table) {
                $table->dropUnique('unique_voucher_no');
                $table->dropColumn('branch_id');
            });

            Schema::table('voucher_nos', function (Blueprint $table) {
                $table->unique(['voucher_type_id', 'company_id', 'fiscal_year_id'], 'unique_voucher_no');
            });
        }

        // 2. voucher_classifications: drop branch_id
        if (Schema::hasColumn('voucher_classifications', 'branch_id')) {
            Schema::table('voucher_classifications', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        // 3. documents: drop branch_id (non-nullable column — ensure no rows exist before running)
        if (Schema::hasColumn('documents', 'branch_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        // 4. tickets: drop branch_id
        if (Schema::hasColumn('tickets', 'branch_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }
    }

    public function down(): void
    {
        // Reversing is destructive/unsafe — do not roll back.
        // If a rollback is genuinely needed, re-add branch_id as nullable on each
        // table and re-create the voucher_nos unique index. Data values cannot be
        // recovered, so this is a one-way migration.
    }
};
