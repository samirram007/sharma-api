<?php

namespace Modules\Supplier\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Supplier\Contracts\SupplierServiceInterface;

class SupplierFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SupplierServiceInterface::class;
    }
}
