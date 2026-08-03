<?php

namespace Modules\StockItemPrice\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemPrice\Contracts\StockItemPriceServiceInterface;

class StockItemPriceFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemPriceServiceInterface::class;
    }
}
