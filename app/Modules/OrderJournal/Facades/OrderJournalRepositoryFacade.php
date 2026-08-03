<?php

namespace Modules\OrderJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\OrderJournal\Contracts\OrderJournalRepositoryInterface;

class OrderJournalRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OrderJournalRepositoryInterface::class;
    }
}
