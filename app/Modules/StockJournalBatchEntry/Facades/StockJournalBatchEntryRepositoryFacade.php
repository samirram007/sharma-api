<?php

namespace Modules\StockJournalBatchEntry\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryRepositoryInterface;

class StockJournalBatchEntryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StockJournalBatchEntryRepositoryInterface::class;
    }
}
