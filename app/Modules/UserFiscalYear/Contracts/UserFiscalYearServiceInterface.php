<?php

namespace Modules\UserFiscalYear\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Modules\UserFiscalYear\Models\UserFiscalYear;

interface UserFiscalYearServiceInterface extends BaseServiceInterface
{
    public function getByUserId(int $userId): ?UserFiscalYear;

    public function saveReportingPeriod(array $data): UserFiscalYear;
}
