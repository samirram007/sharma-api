<?php

namespace Modules\EmployeeGroup\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\EmployeeGroup\Contracts\EmployeeGroupServiceInterface;

class EmployeeGroupFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmployeeGroupServiceInterface::class;
    }
}
