<?php

namespace Modules\StockJournalStorageUnitEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalStorageUnitEntry\Contracts\StockJournalStorageUnitEntryServiceInterface;

class StockJournalStorageUnitEntryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalStorageUnitEntryServiceInterface::class;
    }
}
