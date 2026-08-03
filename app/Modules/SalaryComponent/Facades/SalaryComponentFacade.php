<?php

namespace Modules\SalaryComponent\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\SalaryComponent\Contracts\SalaryComponentServiceInterface;

class SalaryComponentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SalaryComponentServiceInterface::class;
    }
}
