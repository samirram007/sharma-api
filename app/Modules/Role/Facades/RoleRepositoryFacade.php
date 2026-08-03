<?php

namespace Modules\Role\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Role\Contracts\RoleRepositoryInterface;

class RoleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RoleRepositoryInterface::class;
    }
}
