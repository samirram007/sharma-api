<?php

namespace Modules\StockItemBatch\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemBatch\Contracts\StockItemBatchRepositoryInterface;

class StockItemBatchRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemBatchRepositoryInterface::class;
    }
}
