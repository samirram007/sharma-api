<?php

namespace Modules\Customer\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Customer\Contracts\CustomerServiceInterface;

class CustomerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CustomerServiceInterface::class;
    }
}
