<?php

namespace Modules\DeliveryVehicle\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DeliveryVehicle\Contracts\DeliveryVehicleServiceInterface;

class DeliveryVehicleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryVehicleServiceInterface::class;
    }
}
