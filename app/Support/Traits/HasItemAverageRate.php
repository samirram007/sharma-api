<?php

namespace App\Support\Traits;

use App\Enums\MovementType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait HasItemAverageRate
{
    /**
     * Compute the weighted average rate for a stock item from its inward entries
     * in the given fiscal year (excluding closing entries which have no rate).
     *
     * When an as-of date is provided, only inward entries on or before that date
     * are valued (used for reporting-period-scoped closing stock previews).
     */
    protected function getItemAverageRate(int $stockItemId, int $fiscalYearId, ?Carbon $asOfDate = null): float
    {
        $result = DB::table('stock_journal_entries')
            ->join('stock_journals', 'stock_journal_entries.stock_journal_id', '=', 'stock_journals.id')
            ->join('vouchers', 'stock_journals.id', '=', 'vouchers.stock_journal_id')
            ->where('stock_journal_entries.stock_item_id', $stockItemId)
            ->where('stock_journal_entries.movement_type', MovementType::IN->value)
            ->where('stock_journal_entries.actual_quantity', '>', 0)
            ->where('stock_journal_entries.amount', '>', 0)
            ->where('stock_journals.type', '!=', 'CLOSING')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->when($asOfDate, fn ($query) => $query->where('vouchers.voucher_date', '<=', $asOfDate))
            ->whereNotExists(function ($query) {
                $query->from('stock_journal_entry_purges')
                    ->whereColumn('stock_journal_entry_purges.stock_journal_entry_id', 'stock_journal_entries.id');
            })
            ->selectRaw('CASE WHEN SUM(stock_journal_entries.actual_quantity) > 0 THEN SUM(stock_journal_entries.amount) * 1.0 / SUM(stock_journal_entries.actual_quantity) ELSE 0 END as avg_rate')
            ->value('avg_rate');

        return (float) ($result ?? 0);
    }
}
