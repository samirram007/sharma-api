<?php

namespace Modules\StockItemGodown\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemGodown\Contracts\StockItemGodownRepositoryInterface;

class StockItemGodownRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemGodownRepositoryInterface::class;
    }
}
