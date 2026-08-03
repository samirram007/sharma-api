<?php

namespace Modules\AccountNature\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountNature\Contracts\AccountNatureServiceInterface;

class AccountNatureFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountNatureServiceInterface::class;
    }
}
