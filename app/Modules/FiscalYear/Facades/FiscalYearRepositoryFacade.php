<?php

namespace Modules\FiscalYear\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\FiscalYear\Contracts\FiscalYearRepositoryInterface;

class FiscalYearRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FiscalYearRepositoryInterface::class;
    }
}
