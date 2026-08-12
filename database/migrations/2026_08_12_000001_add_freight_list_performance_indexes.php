<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes that back the freight delivery-note list page
 * (GET /freights/delivery_note).
 *
 * The list query filters vouchers by voucher_type_id + fiscal_year_id +
 * voucher_date (then orders by voucher_date/voucher_no), filters by zone
 * through stock_journals → stock_journal_entries →
 * stock_journal_godown_entries, and eager-loads voucher_party /
 * voucher_dispatch_detail / voucher_entries.account_ledger. Without these
 * indexes every filtered row forces a full table scan of the joined tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Delivery-note list: primary filter + sort columns
        Schema::table('vouchers', function (Blueprint $table) {
            $table->index(
                ['voucher_type_id', 'fiscal_year_id', 'voucher_date'],
                'idx_vouchers_type_fy_date'
            );
        });

        // Zone filter path: vouchers → stock_journals → stock_journal_entries
        // → stock_journal_godown_entries
        Schema::table('stock_journals', function (Blueprint $table) {
            $table->index('voucher_id', 'idx_stock_journals_voucher_id');
        });
        Schema::table('stock_journal_entries', function (Blueprint $table) {
            $table->index('stock_journal_id', 'idx_stock_journal_entries_journal_id');
        });
        Schema::table('stock_journal_godown_entries', function (Blueprint $table) {
            $table->index('stock_journal_entry_id', 'idx_sjge_stock_journal_entry_id');
            $table->index('godown_id', 'idx_sjge_godown_id');
        });

        // Eager loads on the list response
        Schema::table('voucher_parties', function (Blueprint $table) {
            $table->index('voucher_id', 'idx_voucher_parties_voucher_id');
        });

        // attachListInfo() grouped balance queries filter by account_ledger_id
        // (and voucher_references lookups filter by ref_voucher_id)
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->index('account_ledger_id', 'idx_voucher_entries_account_ledger_id');
        });
        Schema::table('voucher_references', function (Blueprint $table) {
            $table->index('ref_voucher_id', 'idx_voucher_references_ref_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex('idx_vouchers_type_fy_date');
        });
        Schema::table('stock_journals', function (Blueprint $table) {
            $table->dropIndex('idx_stock_journals_voucher_id');
        });
        Schema::table('stock_journal_entries', function (Blueprint $table) {
            $table->dropIndex('idx_stock_journal_entries_journal_id');
        });
        Schema::table('stock_journal_godown_entries', function (Blueprint $table) {
            $table->dropIndex('idx_sjge_stock_journal_entry_id');
            $table->dropIndex('idx_sjge_godown_id');
        });
        Schema::table('voucher_parties', function (Blueprint $table) {
            $table->dropIndex('idx_voucher_parties_voucher_id');
        });
        Schema::table('voucher_entries', function (Blueprint $table) {
            $table->dropIndex('idx_voucher_entries_account_ledger_id');
        });
        Schema::table('voucher_references', function (Blueprint $table) {
            $table->dropIndex('idx_voucher_references_ref_voucher_id');
        });
    }
};
