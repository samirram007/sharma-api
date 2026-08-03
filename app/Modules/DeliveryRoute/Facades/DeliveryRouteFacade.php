<?php

namespace Modules\DeliveryRoute\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DeliveryRoute\Contracts\DeliveryRouteServiceInterface;

class DeliveryRouteFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryRouteServiceInterface::class;
    }
}
