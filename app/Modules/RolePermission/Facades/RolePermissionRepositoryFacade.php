<?php

namespace Modules\RolePermission\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\RolePermission\Contracts\RolePermissionRepositoryInterface;

class RolePermissionRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RolePermissionRepositoryInterface::class;
    }
}
