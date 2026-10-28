<?php

namespace Modules\Voucher\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Voucher\Contracts\DeliveryLockServiceInterface;

class DeliveryLockFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DeliveryLockServiceInterface::class;
    }
}
