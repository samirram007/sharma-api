<?php

namespace Modules\VoucherPaymentMode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeRepositoryInterface;

class VoucherPaymentModeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherPaymentModeRepositoryInterface::class;
    }
}
