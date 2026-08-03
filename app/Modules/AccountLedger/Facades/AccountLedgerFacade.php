<?php

namespace Modules\AccountLedger\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountLedger\Contracts\AccountLedgerServiceInterface;

class AccountLedgerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountLedgerServiceInterface::class;
    }
}
