<?php

namespace Modules\Ticket\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Ticket\Contracts\TicketServiceInterface;

class TicketFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TicketServiceInterface::class;
    }
}
