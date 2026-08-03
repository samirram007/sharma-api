<?php

namespace Modules\FiscalYearOpen\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\FiscalYearOpen\Contracts\FiscalYearOpenServiceInterface;

class FiscalYearOpenFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FiscalYearOpenServiceInterface::class;
    }
}
