<?php

namespace Modules\DeliveryPlace\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DeliveryPlace\Contracts\DeliveryPlaceRepositoryInterface;

class DeliveryPlaceRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryPlaceRepositoryInterface::class;
    }
}
