<?php

namespace Modules\Voucher\Facades;

use Illuminate\Support\Facades\Facade;

class DeliveryLockRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Voucher\Contracts\DeliveryLockRepositoryInterface::class;
    }
}