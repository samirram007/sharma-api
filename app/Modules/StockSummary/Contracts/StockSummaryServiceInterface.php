<?php

namespace Modules\StockSummary\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Support\Carbon;
use Modules\StockSummary\Models\StockSummary;

interface StockSummaryServiceInterface extends BaseServiceInterface
{
    /**
     * Closing stock for the current fiscal year. Returns the frozen closing stock
     * journal entries when a CLSSK closing journal exists, otherwise computes the
     * running closing stock live from stock movements (item → godown → batch).
     */
    public function closingStock(): array;

    /**
     * Running closing stock (net balance per item/godown/batch) for a given
     * fiscal year, computed live from stock movements. Fallback source when no
     * frozen CLSSK closing journal exists for the fiscal year.
     */
    public function runningClosingStockItems(int $fiscalYearId, ?Carbon $asOfDate = null): array;

    public function stockInHand(): array;

    public function stock_in_hand_item_wise(): array;

    public function stock_in_hand_godown_wise(): array;

    public function stock_in_hand_voucher_wise(): array;

    /**
     * Get all items with opening/operating/closing summary for the running balance grid.
     */
    public function getRunningBalanceItems(): array;

    /**
     * Get detailed running balance for a single item with chronological transactions
     * and a running balance (cumulative) column. Optionally filter by godown.
     */
    public function getRunningBalance(int $itemId, ?int $godownId = null): array;

    /**
     * Get godown-level running balance summary (opening/inward/outward/closing per godown).
     */
    public function getRunningBalanceGodowns(): array;

    /**
     * Get items within a specific godown with their running balance quantities.
     */
    public function getGodownRunningBalanceItems(int $godownId): array;

    /**
     * Opening Stock report — the opening voucher's stock broken down item-wise
     * and godown-wise, with batch detail lines. Optionally narrowed to one
     * godown and/or item.
     */
    public function getOpeningStockReport(?int $godownId = null, ?int $itemId = null): array;

    /**
     * Distinct batch numbers (with item/godown context) for the batch-movement
     * report's search suggestions. Optionally filtered by a search string
     * and/or a specific item/godown so the suggestions match the chosen filters.
     */
    public function getBatchList(?string $search = null, int $limit = 200, ?int $itemId = null, ?int $godownId = null): array;

    /**
     * Batch Movement report — full chronological movement of batches with a
     * running balance, item and godown wise. Filters: search, item_id,
     * godown_id, batch_no, from_date, to_date.
     */
    public function getBatchMovements(array $filters = []): array;

    public function netStock(array $data): StockSummary;

    public function purchaseOrderOutstanding(): StockSummary;

    public function salebleStock(): StockSummary;

    public function salesOrderOutstanding(): StockSummary;
}
