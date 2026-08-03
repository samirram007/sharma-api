<?php

namespace Modules\CostAllocationRule\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CostAllocationRule\Contracts\CostAllocationRuleServiceInterface;

class CostAllocationRuleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CostAllocationRuleServiceInterface::class;
    }
}
