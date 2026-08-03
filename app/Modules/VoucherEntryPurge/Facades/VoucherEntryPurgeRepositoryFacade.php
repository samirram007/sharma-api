<?php

namespace Modules\VoucherEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeRepositoryInterface;

class VoucherEntryPurgeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherEntryPurgeRepositoryInterface::class;
    }
}
