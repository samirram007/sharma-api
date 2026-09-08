<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes that cover the exact query patterns used by the
     * stock-in-hand report family. Each index is shaped so MariaDB can
     * use it as a covering or range-scan index for the relevant join path.
     *
     * No row data is modified — pure read-only index additions.
     */
    public function up(): void
    {
        // ── vouchers(fiscal_year_id, voucher_date, stock_journal_id) ──────
        // Covers both query patterns:
        //
        //  Pattern A — EXISTS subquery (withWhereHas, 3 reports):
        //    The inner voucher filter is `v.fiscal_year_id = ? AND v.voucher_date <= ?`
        //    and the join to stock_journals uses `v.stock_journal_id`.
        //    A leftmost prefix on (fiscal_year_id, voucher_date) lets MariaDB do a
        //    range scan for the date window, with stock_journal_id as a covering
        //    column so the join to stock_journals never touches the table row.
        //
        //  Pattern B — flat JOIN (raw query, 2 reports):
        //    Same filter + join shape; the composite serves as a covering index
        //    so MariaDB can resolve the voucher side without a table lookup.
        //
        // The earlier idx_vouchers_stock_journal_id (single-column) is still
        // useful for queries that join on stock_journal_id *without* a
        // fiscal_year_id filter, so it is kept.
        Schema::table('vouchers', function (Blueprint $table) {
            $table->index(
                ['fiscal_year_id', 'voucher_date', 'stock_journal_id'],
                'idx_vouchers_fy_date_stock_journal',
            );
        });

        // ── stock_journal_entries(stock_journal_id, stock_item_id) ─────────
        // The flat-JOIN path (godown_wise / zone_wise raw queries) joins
        //
        //   vouchers → stock_journals → stock_journal_entries → stock_items
        //
        // and filters vouchers first. Once MariaDB has a set of stock_journal
        // ids (from the voucher side), it needs to look up all entries for
        // those journals and then their stock_items. A composite starting with
        // stock_journal_id gives an equi-join on the leading column; stock_item_id
        // as the second column makes the join to stock_items a covering lookup.
        //
        // This is the reverse column order of the existing
        // idx_sje_stock_item_id (stock_item_id first), which still serves the
        // Eloquent eager-load path (query #2 / #3 above — load entries for a
        // known set of stock_item ids). Both indexes are complementary.
        Schema::table('stock_journal_entries', function (Blueprint $table) {
            $table->index(
                ['stock_journal_id', 'stock_item_id'],
                'idx_sje_journal_item',
            );
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex('idx_vouchers_fy_date_stock_journal');
        });

        Schema::table('stock_journal_entries', function (Blueprint $table) {
            $table->dropIndex('idx_sje_journal_item');
        });
    }
};
