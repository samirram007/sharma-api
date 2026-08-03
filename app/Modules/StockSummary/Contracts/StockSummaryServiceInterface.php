<?php

namespace Modules\StockSummary\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Modules\StockSummary\Models\StockSummary;

interface StockSummaryServiceInterface extends BaseServiceInterface
{
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

    public function netStock(array $data): StockSummary;

    public function purchaseOrderOutstanding(): StockSummary;

    public function salebleStock(): StockSummary;

    public function salesOrderOutstanding(): StockSummary;
}
