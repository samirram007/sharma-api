<?php

namespace Modules\SalaryStructure\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\SalaryStructure\Contracts\SalaryStructureServiceInterface;

class SalaryStructureFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SalaryStructureServiceInterface::class;
    }
}
