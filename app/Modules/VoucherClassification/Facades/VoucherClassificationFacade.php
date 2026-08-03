<?php

namespace Modules\VoucherClassification\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherClassification\Contracts\VoucherClassificationServiceInterface;

class VoucherClassificationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherClassificationServiceInterface::class;
    }
}
