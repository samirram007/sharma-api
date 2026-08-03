<?php

namespace Modules\VoucherDispatchDetail\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;

class VoucherDispatchDetailFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherDispatchDetailServiceInterface::class;
    }
}
