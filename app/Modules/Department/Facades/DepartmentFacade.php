<?php

namespace Modules\Department\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Department\Contracts\DepartmentServiceInterface;

class DepartmentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DepartmentServiceInterface::class;
    }
}
