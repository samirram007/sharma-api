<?php

namespace Modules\UserRole\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UserRole\Contracts\UserRoleRepositoryInterface;

class UserRoleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UserRoleRepositoryInterface::class;
    }
}
