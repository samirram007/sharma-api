<?php

namespace Modules\Department\Services;

use App\Support\Services\BaseService;
use Modules\Department\Contracts\DepartmentServiceInterface;
use Modules\Department\Facades\DepartmentRepositoryFacade;
use Modules\Department\Models\Department;

class DepartmentService extends BaseService implements DepartmentServiceInterface
{
    protected string $modelClass = Department::class;

    protected string $repositoryFacadeClass = DepartmentRepositoryFacade::class;

    public function __construct() {}
}
