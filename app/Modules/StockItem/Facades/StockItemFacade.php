<?php

namespace Modules\StockItem\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItem\Contracts\StockItemServiceInterface;

class StockItemFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemServiceInterface::class;
    }
}
