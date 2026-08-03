<?php

namespace Modules\StockItemPrice\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemPrice\Contracts\StockItemPriceRepositoryInterface;

class StockItemPriceRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemPriceRepositoryInterface::class;
    }
}
