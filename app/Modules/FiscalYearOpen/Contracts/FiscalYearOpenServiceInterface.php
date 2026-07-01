<?php

namespace Modules\FiscalYearOpen\Contracts;

interface FiscalYearOpenServiceInterface
{
    /**
     * Open a new fiscal year by:
     * - Creating opening account voucher with carry-forward balances
     * - Creating opening stock voucher with carry-forward quantities
     * - Updating user fiscal years to point to the new FY
     */
    public function open(int $newFiscalYearId, int $previousFiscalYearId): array;

    /**
     * Preview what would be carried forward before opening
     */
    public function preview(int $newFiscalYearId, int $previousFiscalYearId): array;
}
