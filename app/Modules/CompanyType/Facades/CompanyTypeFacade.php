<?php

namespace Modules\CompanyType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\CompanyType\Contracts\CompanyTypeServiceInterface;

class CompanyTypeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CompanyTypeServiceInterface::class;
    }
}
