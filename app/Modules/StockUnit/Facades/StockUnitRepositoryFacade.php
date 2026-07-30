<?php

namespace Modules\StockUnit\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockUnit\Contracts\StockUnitRepositoryInterface;

class StockUnitRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockUnitRepositoryInterface::class;
    }
}
