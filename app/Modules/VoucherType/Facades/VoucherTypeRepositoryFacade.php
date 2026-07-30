<?php

namespace Modules\VoucherType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherType\Contracts\VoucherTypeRepositoryInterface;

class VoucherTypeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherTypeRepositoryInterface::class;
    }
}
