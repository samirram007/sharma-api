<?php

namespace Modules\SalaryComponent\Services;

use App\Support\Services\BaseService;
use Modules\SalaryComponent\Contracts\SalaryComponentServiceInterface;
use Modules\SalaryComponent\Facades\SalaryComponentRepositoryFacade;
use Modules\SalaryComponent\Models\SalaryComponent;

class SalaryComponentService extends BaseService implements SalaryComponentServiceInterface
{
    protected string $modelClass = SalaryComponent::class;

    protected string $repositoryFacadeClass = SalaryComponentRepositoryFacade::class;

    public function __construct() {}
}
