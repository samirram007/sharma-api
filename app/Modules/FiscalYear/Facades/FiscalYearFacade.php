<?php

namespace Modules\FiscalYear\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\FiscalYear\Contracts\FiscalYearServiceInterface;

class FiscalYearFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FiscalYearServiceInterface::class;
    }
}
