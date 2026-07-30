<?php

namespace Modules\AccountGroup\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountGroup\Contracts\AccountGroupServiceInterface;

class AccountGroupFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountGroupServiceInterface::class;
    }
}
