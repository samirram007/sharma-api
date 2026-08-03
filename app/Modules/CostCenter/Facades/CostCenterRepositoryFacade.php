<?php

namespace Modules\CostCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CostCenter\Contracts\CostCenterRepositoryInterface;

class CostCenterRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CostCenterRepositoryInterface::class;
    }
}
