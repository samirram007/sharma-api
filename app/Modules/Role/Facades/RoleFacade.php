<?php

namespace Modules\Role\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Role\Contracts\RoleServiceInterface;

class RoleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RoleServiceInterface::class;
    }
}
