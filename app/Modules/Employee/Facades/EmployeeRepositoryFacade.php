<?php

namespace Modules\Employee\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Employee\Contracts\EmployeeRepositoryInterface;

class EmployeeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmployeeRepositoryInterface::class;
    }
}
