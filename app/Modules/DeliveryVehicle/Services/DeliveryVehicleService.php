<?php

namespace Modules\DeliveryVehicle\Services;

use App\Support\Services\BaseService;
use Modules\DeliveryVehicle\Contracts\DeliveryVehicleServiceInterface;
use Modules\DeliveryVehicle\Facades\DeliveryVehicleRepositoryFacade;
use Modules\DeliveryVehicle\Models\DeliveryVehicle;

class DeliveryVehicleService extends BaseService implements DeliveryVehicleServiceInterface
{
    protected string $modelClass = DeliveryVehicle::class;

    protected array $defaultResource = [
        'transporter',
    ];

    protected string $repositoryFacadeClass = DeliveryVehicleRepositoryFacade::class;

    public function __construct() {}
}
