<?php

namespace Modules\StockJournalEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeRepositoryInterface;

class StockJournalEntryPurgeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalEntryPurgeRepositoryInterface::class;
    }
}
