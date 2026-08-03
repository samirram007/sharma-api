<?php

namespace Modules\VoucherType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherType\Contracts\VoucherTypeServiceInterface;

class VoucherTypeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherTypeServiceInterface::class;
    }
}
