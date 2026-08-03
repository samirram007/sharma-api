<?php

namespace Modules\User\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\User\Contracts\UserServiceInterface;

class UserFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UserServiceInterface::class;
    }
}
