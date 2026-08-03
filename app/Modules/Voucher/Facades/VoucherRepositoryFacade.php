<?php

namespace Modules\Voucher\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Voucher\Contracts\VoucherRepositoryInterface;

class VoucherRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherRepositoryInterface::class;
    }
}
