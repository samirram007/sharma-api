<?php

namespace Modules\PaymentVoucher\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\PaymentVoucher\Contracts\PaymentVoucherServiceInterface;

class PaymentVoucherFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentVoucherServiceInterface::class;
    }
}
