<?php

namespace Modules\AccountingPeriod\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountingPeriod\Contracts\AccountingPeriodServiceInterface;

class AccountingPeriodFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountingPeriodServiceInterface::class;
    }
}
