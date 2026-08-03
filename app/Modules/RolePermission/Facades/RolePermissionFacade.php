<?php

namespace Modules\RolePermission\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\RolePermission\Contracts\RolePermissionServiceInterface;

class RolePermissionFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RolePermissionServiceInterface::class;
    }
}
