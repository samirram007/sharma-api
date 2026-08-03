<?php

namespace Modules\StockUnit\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockUnit\Contracts\StockUnitServiceInterface;

class StockUnitFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockUnitServiceInterface::class;
    }
}
