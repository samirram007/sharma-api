<?php

namespace Modules\Company\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Company\Contracts\CompanyServiceInterface;

class CompanyFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CompanyServiceInterface::class;
    }
}
