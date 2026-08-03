<?php

namespace Modules\StockJournalBatchEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryServiceInterface;

class StockJournalBatchEntryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalBatchEntryServiceInterface::class;
    }
}
