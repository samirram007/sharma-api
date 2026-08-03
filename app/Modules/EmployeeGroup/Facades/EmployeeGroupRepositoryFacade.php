<?php

namespace Modules\EmployeeGroup\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\EmployeeGroup\Contracts\EmployeeGroupRepositoryInterface;

class EmployeeGroupRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmployeeGroupRepositoryInterface::class;
    }
}
