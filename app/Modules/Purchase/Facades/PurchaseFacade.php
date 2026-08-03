<?php

namespace Modules\Purchase\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Purchase\Contracts\PurchaseServiceInterface;

class PurchaseFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PurchaseServiceInterface::class;
    }
}
