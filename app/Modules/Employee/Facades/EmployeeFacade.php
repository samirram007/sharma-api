<?php

namespace Modules\Employee\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Employee\Contracts\EmployeeServiceInterface;

class EmployeeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmployeeServiceInterface::class;
    }
}
