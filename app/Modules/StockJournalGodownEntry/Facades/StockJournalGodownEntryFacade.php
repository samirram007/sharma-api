<?php

namespace Modules\StockJournalGodownEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalGodownEntry\Contracts\StockJournalGodownEntryServiceInterface;

class StockJournalGodownEntryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalGodownEntryServiceInterface::class;
    }
}
