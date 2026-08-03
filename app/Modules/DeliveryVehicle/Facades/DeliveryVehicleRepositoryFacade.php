<?php

namespace Modules\DeliveryVehicle\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DeliveryVehicle\Contracts\DeliveryVehicleRepositoryInterface;

class DeliveryVehicleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryVehicleRepositoryInterface::class;
    }
}
