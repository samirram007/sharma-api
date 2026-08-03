<?php

namespace Modules\VoucherEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;

class VoucherEntryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherEntryServiceInterface::class;
    }
}
