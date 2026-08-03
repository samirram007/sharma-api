<?php

namespace Modules\CostCategory\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CostCategory\Contracts\CostCategoryRepositoryInterface;

class CostCategoryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CostCategoryRepositoryInterface::class;
    }
}
