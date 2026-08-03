<?php

namespace Modules\OrderStockJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\OrderStockJournal\Contracts\OrderStockJournalServiceInterface;

class OrderStockJournalFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OrderStockJournalServiceInterface::class;
    }
}
