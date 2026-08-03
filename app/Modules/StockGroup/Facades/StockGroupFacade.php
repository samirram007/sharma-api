<?php

namespace Modules\StockGroup\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockGroup\Contracts\StockGroupServiceInterface;

class StockGroupFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockGroupServiceInterface::class;
    }
}
