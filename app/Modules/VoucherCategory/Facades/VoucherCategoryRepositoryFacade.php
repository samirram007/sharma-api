<?php

namespace Modules\VoucherCategory\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherCategory\Contracts\VoucherCategoryRepositoryInterface;

class VoucherCategoryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherCategoryRepositoryInterface::class;
    }
}
