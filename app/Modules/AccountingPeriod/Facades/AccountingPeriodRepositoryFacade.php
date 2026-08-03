<?php

namespace Modules\AccountingPeriod\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountingPeriod\Contracts\AccountingPeriodRepositoryInterface;

class AccountingPeriodRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountingPeriodRepositoryInterface::class;
    }
}
