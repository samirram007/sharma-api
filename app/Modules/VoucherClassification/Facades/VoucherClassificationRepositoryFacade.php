<?php

namespace Modules\VoucherClassification\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherClassification\Contracts\VoucherClassificationRepositoryInterface;

class VoucherClassificationRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherClassificationRepositoryInterface::class;
    }
}
