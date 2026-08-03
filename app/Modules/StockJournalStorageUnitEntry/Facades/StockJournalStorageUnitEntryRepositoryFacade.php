<?php

namespace Modules\StockJournalStorageUnitEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalStorageUnitEntry\Contracts\StockJournalStorageUnitEntryRepositoryInterface;

class StockJournalStorageUnitEntryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalStorageUnitEntryRepositoryInterface::class;
    }
}
