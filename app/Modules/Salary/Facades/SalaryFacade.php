<?php

namespace Modules\Salary\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Salary\Contracts\SalaryServiceInterface;

class SalaryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SalaryServiceInterface::class;
    }
}
