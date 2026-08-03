<?php

namespace Modules\StockItemBrand\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemBrand\Contracts\StockItemBrandRepositoryInterface;

class StockItemBrandRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemBrandRepositoryInterface::class;
    }
}
