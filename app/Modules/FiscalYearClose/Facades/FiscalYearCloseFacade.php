<?php

namespace Modules\FiscalYearClose\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\FiscalYearClose\Contracts\FiscalYearCloseServiceInterface;

class FiscalYearCloseFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FiscalYearCloseServiceInterface::class;
    }
}
