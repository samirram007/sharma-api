<?php

namespace Modules\StockJournalSerialNoEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalSerialNoEntry\Contracts\StockJournalSerialNoEntryServiceInterface;

class StockJournalSerialNoEntryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalSerialNoEntryServiceInterface::class;
    }
}
