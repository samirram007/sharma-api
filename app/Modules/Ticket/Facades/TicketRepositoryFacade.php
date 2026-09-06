<?php

namespace Modules\Ticket\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Ticket\Contracts\TicketRepositoryInterface;

class TicketRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TicketRepositoryInterface::class;
    }
}
