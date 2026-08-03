<?php

namespace Modules\CostCategory\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CostCategory\Contracts\CostCategoryServiceInterface;

class CostCategoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CostCategoryServiceInterface::class;
    }
}
