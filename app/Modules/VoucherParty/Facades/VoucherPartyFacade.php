<?php

namespace Modules\VoucherParty\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;

class VoucherPartyFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherPartyServiceInterface::class;
    }
}
