<?php

namespace Modules\CompanyType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CompanyType\Contracts\CompanyTypeRepositoryInterface;

class CompanyTypeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CompanyTypeRepositoryInterface::class;
    }
}
