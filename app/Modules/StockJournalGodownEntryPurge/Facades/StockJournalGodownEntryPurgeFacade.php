<?php

namespace Modules\StockJournalGodownEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeServiceInterface;

class StockJournalGodownEntryPurgeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalGodownEntryPurgeServiceInterface::class;
    }
}
