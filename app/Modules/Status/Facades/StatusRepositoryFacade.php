<?php

namespace Modules\Status\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Status\Contracts\StatusRepositoryInterface;

class StatusRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StatusRepositoryInterface::class;
    }
}
