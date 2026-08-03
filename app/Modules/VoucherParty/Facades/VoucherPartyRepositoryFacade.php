<?php

namespace Modules\VoucherParty\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\VoucherParty\Contracts\VoucherPartyRepositoryInterface;

class VoucherPartyRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VoucherPartyRepositoryInterface::class;
    }
}
