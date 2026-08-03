<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeRepositoryInterface;

class StockJournalStorageUnitEntryPurgeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalStorageUnitEntryPurgeRepositoryInterface::class;
    }
}
