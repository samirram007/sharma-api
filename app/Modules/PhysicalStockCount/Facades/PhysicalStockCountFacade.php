<?php

namespace Modules\PhysicalStockCount\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\PhysicalStockCount\Contracts\PhysicalStockCountServiceInterface;

class PhysicalStockCountFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PhysicalStockCountServiceInterface::class;
    }
}
