<?php

namespace Modules\Company\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Company\Contracts\CompanyRepositoryInterface;

class CompanyRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CompanyRepositoryInterface::class;
    }
}
