<?php

namespace Modules\StockGroup\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockGroup\Contracts\StockGroupRepositoryInterface;

class StockGroupRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockGroupRepositoryInterface::class;
    }
}
