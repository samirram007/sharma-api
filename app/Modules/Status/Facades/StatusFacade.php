<?php

namespace Modules\Status\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Status\Contracts\StatusServiceInterface;

class StatusFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StatusServiceInterface::class;
    }
}
