<?php

namespace Modules\AccountNature\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountNature\Contracts\AccountNatureRepositoryInterface;

class AccountNatureRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountNatureRepositoryInterface::class;
    }
}
