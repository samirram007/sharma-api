<?php

namespace Modules\CostAllocationRule\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CostAllocationRule\Contracts\CostAllocationRuleRepositoryInterface;

class CostAllocationRuleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CostAllocationRuleRepositoryInterface::class;
    }
}
