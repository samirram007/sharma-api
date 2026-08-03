<?php

namespace Modules\Freight\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Freight\Contracts\FreightServiceInterface;

class FreightFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FreightServiceInterface::class;
    }
}
