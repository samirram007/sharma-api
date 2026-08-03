<?php

namespace Modules\OrderStockJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\OrderStockJournal\Contracts\OrderStockJournalRepositoryInterface;

class OrderStockJournalRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OrderStockJournalRepositoryInterface::class;
    }
}
