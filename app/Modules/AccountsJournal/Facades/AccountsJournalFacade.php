<?php

namespace Modules\AccountsJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountsJournal\Contracts\AccountsJournalServiceInterface;

class AccountsJournalFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountsJournalServiceInterface::class;
    }
}
