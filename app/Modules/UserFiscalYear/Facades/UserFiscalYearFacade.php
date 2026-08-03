<?php

namespace Modules\UserFiscalYear\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;

class UserFiscalYearFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UserFiscalYearServiceInterface::class;
    }
}
