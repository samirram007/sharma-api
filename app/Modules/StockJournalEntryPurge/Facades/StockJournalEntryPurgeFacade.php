<?php

namespace Modules\StockJournalEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeServiceInterface;

class StockJournalEntryPurgeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalEntryPurgeServiceInterface::class;
    }
}
