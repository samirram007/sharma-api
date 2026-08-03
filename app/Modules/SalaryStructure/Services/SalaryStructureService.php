<?php

namespace Modules\SalaryStructure\Services;

use App\Support\Services\BaseService;
use Modules\SalaryStructure\Contracts\SalaryStructureServiceInterface;
use Modules\SalaryStructure\Facades\SalaryStructureRepositoryFacade;
use Modules\SalaryStructure\Models\SalaryStructure;

class SalaryStructureService extends BaseService implements SalaryStructureServiceInterface
{
    protected string $modelClass = SalaryStructure::class;

    protected string $repositoryFacadeClass = SalaryStructureRepositoryFacade::class;

    public function __construct() {}
}
