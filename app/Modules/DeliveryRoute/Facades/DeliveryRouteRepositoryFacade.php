<?php

namespace Modules\DeliveryRoute\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DeliveryRoute\Contracts\DeliveryRouteRepositoryInterface;

class DeliveryRouteRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryRouteRepositoryInterface::class;
    }
}
