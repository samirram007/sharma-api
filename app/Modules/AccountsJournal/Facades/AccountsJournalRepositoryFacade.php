<?php

namespace Modules\AccountsJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AccountsJournal\Contracts\AccountsJournalRepositoryInterface;

class AccountsJournalRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AccountsJournalRepositoryInterface::class;
    }
}
