<?php
namespace Modules\Company\Facades;

use Modules\Company\Contracts\CompanyServiceInterface;
use Illuminate\Support\Facades\Facade;

class CompanyFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CompanyServiceInterface::class;
    }
}
