<?php

namespace Modules\AccountGroup\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountGroup\Contracts\AccountGroupRepositoryInterface;

class AccountGroupRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountGroupRepositoryInterface::class;
    }
}
