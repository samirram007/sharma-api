<?php

namespace Modules\CostCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CostCenter\Contracts\CostCenterServiceInterface;

class CostCenterFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CostCenterServiceInterface::class;
    }
}
