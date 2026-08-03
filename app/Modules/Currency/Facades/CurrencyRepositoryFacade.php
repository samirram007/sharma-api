<?php

namespace Modules\Currency\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Currency\Contracts\CurrencyRepositoryInterface;

class CurrencyRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyRepositoryInterface::class;
    }
}
