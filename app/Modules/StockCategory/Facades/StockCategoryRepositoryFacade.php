<?php

namespace Modules\StockCategory\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockCategory\Contracts\StockCategoryRepositoryInterface;

class StockCategoryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockCategoryRepositoryInterface::class;
    }
}
