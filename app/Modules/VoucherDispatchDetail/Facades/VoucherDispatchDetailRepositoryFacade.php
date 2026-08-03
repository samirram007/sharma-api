<?php

namespace Modules\VoucherDispatchDetail\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailRepositoryInterface;

class VoucherDispatchDetailRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherDispatchDetailRepositoryInterface::class;
    }
}
