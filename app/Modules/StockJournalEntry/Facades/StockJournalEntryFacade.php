<?php

namespace Modules\StockJournalEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalEntry\Contracts\StockJournalEntryServiceInterface;

class StockJournalEntryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalEntryServiceInterface::class;
    }
}
