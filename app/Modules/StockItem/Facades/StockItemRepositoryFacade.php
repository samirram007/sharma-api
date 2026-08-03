<?php

namespace Modules\StockItem\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItem\Contracts\StockItemRepositoryInterface;

class StockItemRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemRepositoryInterface::class;
    }
}
