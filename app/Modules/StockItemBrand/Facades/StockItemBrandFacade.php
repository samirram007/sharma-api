<?php

namespace Modules\StockItemBrand\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockItemBrand\Contracts\StockItemBrandServiceInterface;

class StockItemBrandFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockItemBrandServiceInterface::class;
    }
}
