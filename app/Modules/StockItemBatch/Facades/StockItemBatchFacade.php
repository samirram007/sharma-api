<?php

namespace Modules\StockItemBatch\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemBatch\Contracts\StockItemBatchServiceInterface;

class StockItemBatchFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemBatchServiceInterface::class;
    }
}
