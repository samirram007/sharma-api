<?php

namespace Modules\SalaryStructure\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\SalaryStructure\Contracts\SalaryStructureRepositoryInterface;

class SalaryStructureRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SalaryStructureRepositoryInterface::class;
    }
}
