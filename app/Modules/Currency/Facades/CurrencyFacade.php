<?php

namespace Modules\Currency\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Currency\Contracts\CurrencyServiceInterface;

class CurrencyFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyServiceInterface::class;
    }
}
