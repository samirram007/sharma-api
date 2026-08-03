<?php

namespace Modules\DeliveryPlace\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DeliveryPlace\Contracts\DeliveryPlaceServiceInterface;

class DeliveryPlaceFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryPlaceServiceInterface::class;
    }
}
