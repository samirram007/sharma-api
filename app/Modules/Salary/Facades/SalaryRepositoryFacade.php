<?php

namespace Modules\Salary\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Salary\Contracts\SalaryRepositoryInterface;

class SalaryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SalaryRepositoryInterface::class;
    }
}
