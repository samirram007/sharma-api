<?php

namespace Modules\StockJournalSerialNoEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalSerialNoEntry\Contracts\StockJournalSerialNoEntryRepositoryInterface;

class StockJournalSerialNoEntryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalSerialNoEntryRepositoryInterface::class;
    }
}
