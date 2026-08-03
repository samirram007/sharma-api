<?php

namespace Modules\VoucherReference\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherReference\Contracts\VoucherReferenceServiceInterface;

class VoucherReferenceFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherReferenceServiceInterface::class;
    }
}
