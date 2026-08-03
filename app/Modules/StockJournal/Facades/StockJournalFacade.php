<?php

namespace Modules\StockJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournal\Contracts\StockJournalServiceInterface;

class StockJournalFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalServiceInterface::class;
    }
}
