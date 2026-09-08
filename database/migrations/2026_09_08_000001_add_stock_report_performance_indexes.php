<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes that speed up the stock-in-hand report family
     * (item-wise, godown-wise, zone-wise, voucher-wise, closing-stock).
     *
     * All tables/columns are already in use by those reports — this migration
     * only adds read-only indexes, never touches row data.
     */
    public function up(): void
    {
        // ── vouchers.stock_journal_id ──────────────────────────────────────
        // Every stock report joins vouchers → stock_journals on this column,
        // then filters vouchers by fiscal_year_id / voucher_date. Without an
        // index the stock_journals lookup is a full scan per voucher row.
        Schema::table('vouchers', function (Blueprint $table) {
            // Name follows the project convention (idx_{table}_{column}).
            $table->index('stock_journal_id', 'idx_vouchers_stock_journal_id');
        });

        // ── stock_journal_entries.stock_item_id ────────────────────────────
        // stockInHandQuery() / stockInHandVoucherWiseQuery() use
        // StockItem::withWhereHas('stock_journal_entries.stock_journal.voucher', ...)
        // which resolves to a join on stock_journal_entries.stock_item_id.
        // Indexing this avoids a full scan of stock_journal_entries per stock item.
        Schema::table('stock_journal_entries', function (Blueprint $table) {
            $table->index('stock_item_id', 'idx_sje_stock_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex('idx_vouchers_stock_journal_id');
        });

        Schema::table('stock_journal_entries', function (Blueprint $table) {
            $table->dropIndex('idx_sje_stock_item_id');
        });
    }
};
