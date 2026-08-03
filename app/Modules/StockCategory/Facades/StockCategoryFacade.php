<?php

namespace Modules\StockCategory\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockCategory\Contracts\StockCategoryServiceInterface;

class StockCategoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockCategoryServiceInterface::class;
    }
}
