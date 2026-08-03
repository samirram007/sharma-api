<?php

namespace Modules\LeaveType\Services;

use App\Support\Services\BaseService;
use Modules\LeaveType\Contracts\LeaveTypeServiceInterface;
use Modules\LeaveType\Facades\LeaveTypeRepositoryFacade;
use Modules\LeaveType\Models\LeaveType;

class LeaveTypeService extends BaseService implements LeaveTypeServiceInterface
{
    protected string $modelClass = LeaveType::class;

    protected string $repositoryFacadeClass = LeaveTypeRepositoryFacade::class;

    public function __construct() {}
}
