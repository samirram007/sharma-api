<?php

namespace Modules\OpeningBalance\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\OpeningBalance\Contracts\OpeningBalanceServiceInterface;

class OpeningBalanceFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OpeningBalanceServiceInterface::class;
    }
}
