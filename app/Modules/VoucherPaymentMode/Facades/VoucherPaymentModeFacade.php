<?php

namespace Modules\VoucherPaymentMode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeServiceInterface;

class VoucherPaymentModeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherPaymentModeServiceInterface::class;
    }
}
