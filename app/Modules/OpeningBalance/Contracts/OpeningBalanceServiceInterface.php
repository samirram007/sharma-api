<?php

namespace Modules\OpeningBalance\Contracts;

interface OpeningBalanceServiceInterface
{
    /**
     * Get setup data for opening balance wizard:
     * - Balance sheet ledgers (optionally pre-filled from previous FY closing)
     * - Stock items with godowns (optionally pre-filled from previous FY closing)
     * - Current fiscal year info
     * - Previous fiscal year info (if exists and closed)
     */
    public function getSetupData(): array;

    /**
     * Store opening balance entries:
     * - Creates an OPNJL voucher with voucher entries for ledger balances
     * - Creates stock journal entries for stock quantities
     */
    public function store(array $data): array;

    /**
     * Check if an opening balance already exists for the current fiscal year
     */
    public function getStatus(): array;
}
