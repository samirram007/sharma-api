<?php

namespace Modules\VoucherCategory\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherCategory\Contracts\VoucherCategoryServiceInterface;

class VoucherCategoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherCategoryServiceInterface::class;
    }
}
