<?php

namespace Modules\Voucher\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Voucher\Contracts\VoucherServiceInterface;

class VoucherFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherServiceInterface::class;
    }
}
