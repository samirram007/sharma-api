<?php

namespace Modules\PaymentVoucher\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\PaymentVoucher\Contracts\PaymentVoucherRepositoryInterface;

class PaymentVoucherRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentVoucherRepositoryInterface::class;
    }
}
