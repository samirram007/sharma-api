<?php

namespace Modules\Salary\Services;

use App\Support\Services\BaseService;
use Modules\Salary\Contracts\SalaryServiceInterface;
use Modules\Salary\Facades\SalaryRepositoryFacade;
use Modules\Salary\Models\Salary;

class SalaryService extends BaseService implements SalaryServiceInterface
{
    protected string $modelClass = Salary::class;

    protected string $repositoryFacadeClass = SalaryRepositoryFacade::class;

    public function __construct() {}
}
