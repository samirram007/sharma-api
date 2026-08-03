<?php

namespace Modules\UserRole\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UserRole\Contracts\UserRoleServiceInterface;

class UserRoleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UserRoleServiceInterface::class;
    }
}
