<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeServiceInterface;

class StockJournalStorageUnitEntryPurgeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalStorageUnitEntryPurgeServiceInterface::class;
    }
}
