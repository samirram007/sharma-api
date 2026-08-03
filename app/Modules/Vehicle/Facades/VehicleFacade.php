<?php

namespace Modules\Vehicle\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Vehicle\Contracts\VehicleServiceInterface;

class VehicleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VehicleServiceInterface::class;
    }
}
