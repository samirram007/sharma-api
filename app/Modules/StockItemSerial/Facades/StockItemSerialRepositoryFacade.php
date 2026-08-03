<?php

namespace Modules\StockItemSerial\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemSerial\Contracts\StockItemSerialRepositoryInterface;

class StockItemSerialRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemSerialRepositoryInterface::class;
    }
}
