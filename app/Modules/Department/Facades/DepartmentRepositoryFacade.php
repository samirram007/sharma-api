<?php

namespace Modules\Department\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Department\Contracts\DepartmentRepositoryInterface;

class DepartmentRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DepartmentRepositoryInterface::class;
    }
}
