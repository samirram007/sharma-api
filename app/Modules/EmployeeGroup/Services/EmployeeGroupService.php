<?php

namespace Modules\EmployeeGroup\Services;

use App\Support\Services\BaseService;
use Modules\EmployeeGroup\Contracts\EmployeeGroupServiceInterface;
use Modules\EmployeeGroup\Facades\EmployeeGroupRepositoryFacade;
use Modules\EmployeeGroup\Models\EmployeeGroup;

class EmployeeGroupService extends BaseService implements EmployeeGroupServiceInterface
{
    protected string $modelClass = EmployeeGroup::class;

    protected array $defaultResource = [
        'employees',
    ];

    protected string $repositoryFacadeClass = EmployeeGroupRepositoryFacade::class;

    public function __construct() {}
}
