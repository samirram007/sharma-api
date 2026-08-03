<?php

namespace Modules\AccountLedger\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountLedger\Contracts\AccountLedgerRepositoryInterface;

class AccountLedgerRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountLedgerRepositoryInterface::class;
    }
}
