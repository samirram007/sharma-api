<?php

namespace Modules\CostCenter\Services;

use App\Support\Services\BaseService;
use Modules\CostCenter\Contracts\CostCenterServiceInterface;
use Modules\CostCenter\Facades\CostCenterRepositoryFacade;
use Modules\CostCenter\Models\CostCenter;

class CostCenterService extends BaseService implements CostCenterServiceInterface
{
    protected string $modelClass = CostCenter::class;

    protected string $repositoryFacadeClass = CostCenterRepositoryFacade::class;

    public function __construct() {}
}
