<?php

namespace Modules\Vehicle\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Vehicle\Contracts\VehicleRepositoryInterface;

class VehicleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VehicleRepositoryInterface::class;
    }
}
