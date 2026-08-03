<?php

namespace Modules\StockItemSerial\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemSerial\Contracts\StockItemSerialServiceInterface;

class StockItemSerialFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemSerialServiceInterface::class;
    }
}
