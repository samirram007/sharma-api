<?php

namespace Modules\OrderJournal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\OrderJournal\Contracts\OrderJournalServiceInterface;

class OrderJournalFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OrderJournalServiceInterface::class;
    }
}
