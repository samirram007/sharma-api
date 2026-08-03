<?php

namespace Modules\StockItemGodown\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemGodown\Contracts\StockItemGodownServiceInterface;

class StockItemGodownFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemGodownServiceInterface::class;
    }
}
