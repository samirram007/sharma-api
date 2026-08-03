<?php

namespace Modules\UserFiscalYear\Services;

use App\Support\Services\BaseService;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Models\UserFiscalYear;

class UserFiscalYearService extends BaseService implements UserFiscalYearServiceInterface
{
    protected string $modelClass = UserFiscalYear::class;

    protected array $defaultResource = ['user', 'fiscal_year.company'];

    public function saveReportingPeriod(array $data): UserFiscalYear
    {
        $userId = auth()->id();
        $record = UserFiscalYear::where('user_id', $userId)->first();

        if (! $record) {
            throw new \Exception('Reporting period cannot be set. UserFiscalYear not found for the user.');
        }

        $record->update($data);

        return $record->fresh();
    }

    public function getByUserId(int $userId): ?UserFiscalYear
    {
        return UserFiscalYear::with($this->defaultResource)->where('user_id', $userId)->first();
    }
}
