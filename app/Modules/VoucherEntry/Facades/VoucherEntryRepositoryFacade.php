<?php

namespace Modules\VoucherEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherEntry\Contracts\VoucherEntryRepositoryInterface;

class VoucherEntryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherEntryRepositoryInterface::class;
    }
}
