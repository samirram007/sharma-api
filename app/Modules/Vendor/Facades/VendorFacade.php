<?php

namespace Modules\Vendor\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Vendor\Contracts\VendorServiceInterface;

class VendorFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VendorServiceInterface::class;
    }
}
