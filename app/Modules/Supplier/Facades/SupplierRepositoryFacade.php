<?php

namespace Modules\Supplier\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Supplier\Contracts\SupplierRepositoryInterface;

class SupplierRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SupplierRepositoryInterface::class;
    }
}
