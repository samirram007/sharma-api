<?php

namespace Modules\UserFiscalYear\Services;

use App\Support\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\UserFiscalYear\Models\UserFiscalYear;

class UserFiscalYearService extends BaseService implements UserFiscalYearServiceInterface
{
    protected string $modelClass = UserFiscalYear::class;

    protected array $defaultResource = ['user', 'fiscal_year.company'];

    public function saveReportingPeriod(array $data): UserFiscalYear
    {
        $userId = Auth::id();
        $record = UserFiscalYear::where('user_id', $userId)->first();

        if (! $record) {
            throw new \Exception('Reporting period cannot be set. UserFiscalYear not found for the user.');
        }

        // Normalize to a plain Y-m-d date string before writing: the columns
        // are DATEs, and clients may send ISO-8601 datetimes (or JS Dates
        // serialized by axios). Writing the raw string can store a spurious
        // time component (or fail strict-mode checks on MariaDB).
        foreach (['start_date', 'end_date'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::parse($data[$field])->toDateString();
            }
        }

        $record->update($data);

        return $record->fresh();
    }

    public function getByUserId(int $userId): ?UserFiscalYear
    {
        return UserFiscalYear::with($this->defaultResource)->where('user_id', $userId)->first();
    }
}
