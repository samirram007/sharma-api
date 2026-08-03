<?php

namespace Modules\AppMaintenance\Services;

use App\Support\Services\BaseService;
use Modules\AppMaintenance\Contracts\AppMaintenanceServiceInterface;
use Modules\AppMaintenance\Facades\AppMaintenanceRepositoryFacade;
use Modules\AppMaintenance\Models\AppMaintenance;

class AppMaintenanceService extends BaseService implements AppMaintenanceServiceInterface
{
    protected string $modelClass = AppMaintenance::class;

    protected string $repositoryFacadeClass = AppMaintenanceRepositoryFacade::class;

    public function __construct() {}
}
