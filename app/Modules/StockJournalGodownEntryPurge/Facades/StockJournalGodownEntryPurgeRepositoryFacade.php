<?php

namespace Modules\StockJournalGodownEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeRepositoryInterface;

class StockJournalGodownEntryPurgeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalGodownEntryPurgeRepositoryInterface::class;
    }
}
