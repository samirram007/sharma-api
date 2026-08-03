<?php

namespace Modules\Vehicle\Services;

use App\Support\Services\BaseService;
use Modules\Vehicle\Contracts\VehicleServiceInterface;
use Modules\Vehicle\Facades\VehicleRepositoryFacade;
use Modules\Vehicle\Models\Vehicle;

class VehicleService extends BaseService implements VehicleServiceInterface
{
    protected string $modelClass = Vehicle::class;

    protected string $repositoryFacadeClass = VehicleRepositoryFacade::class;

    public function __construct() {}
}
