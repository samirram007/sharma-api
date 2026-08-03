<?php

namespace Modules\VoucherEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeServiceInterface;

class VoucherEntryPurgeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherEntryPurgeServiceInterface::class;
    }
}
