<?php

namespace Modules\SalaryComponent\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\SalaryComponent\Contracts\SalaryComponentRepositoryInterface;

class SalaryComponentRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SalaryComponentRepositoryInterface::class;
    }
}
