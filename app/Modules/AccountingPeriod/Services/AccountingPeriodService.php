<?php

namespace Modules\AccountingPeriod\Services;

use App\Support\Services\BaseService;
use Modules\AccountingPeriod\Contracts\AccountingPeriodServiceInterface;
use Modules\AccountingPeriod\Facades\AccountingPeriodRepositoryFacade;
use Modules\AccountingPeriod\Models\AccountingPeriod;

class AccountingPeriodService extends BaseService implements AccountingPeriodServiceInterface
{
    protected string $modelClass = AccountingPeriod::class;

    protected string $repositoryFacadeClass = AccountingPeriodRepositoryFacade::class;

    public function __construct() {}
}
