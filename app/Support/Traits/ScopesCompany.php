<?php

namespace App\Support\Traits;

use Modules\FiscalYear\Models\FiscalYear;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;

/**
 * Multi-company scoping helpers.
 *
 * The current user's company is derived from their active UserFiscalYear record
 * (user_fiscal_years.fiscal_year_id → fiscal_years.company_id), matching the
 * pattern used by VoucherService. Services using this trait must have a
 * UserFiscalYearServiceInterface dependency named $userFiscalYearService.
 */
trait ScopesCompany
{
    /**
     * Get the current user's company id from their active fiscal year.
     */
    protected function currentUserCompanyId(): int
    {
        $userId = auth()->id();

        if ($userId === null) {
            throw new \Exception('No authenticated user to resolve the company from.');
        }

        $userFiscalYear = $this->userFiscalYearService->getByUserId($userId);

        if (! $userFiscalYear?->fiscal_year) {
            throw new \Exception('No active fiscal year found for the current user.');
        }

        return $userFiscalYear->fiscal_year->company_id;
    }

    /**
     * Assert that a fiscal year belongs to the current user's company.
     */
    protected function validateCompanyAccess(FiscalYear $fiscalYear): void
    {
        $companyId = $this->currentUserCompanyId();

        if ($fiscalYear->company_id !== $companyId) {
            throw new \Exception(
                "Fiscal Year '{$fiscalYear->name}' belongs to a different company and cannot be accessed."
            );
        }
    }
}
