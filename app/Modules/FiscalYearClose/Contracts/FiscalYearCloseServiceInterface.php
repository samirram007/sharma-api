<?php

namespace App\Modules\FiscalYearClose\Contracts;

use App\Modules\FiscalYear\Models\FiscalYear;

interface FiscalYearCloseServiceInterface
{
    /**
     * Close a fiscal year:
     * - Create closing account voucher (P&L transfer to capital)
     * - Create closing stock voucher (freeze stock quantities)
     * - Mark fiscal year as inactive with closed_at timestamp
     */
    public function close(int $fiscalYearId): array;

    /**
     * Reopen a previously closed fiscal year:
     * - Delete closing vouchers
     * - Restore fiscal year status to active
     */
    public function reopen(int $fiscalYearId): array;

    /**
     * Get closing summary data for preview before confirming close
     */
    public function preview(int $fiscalYearId): array;
}
