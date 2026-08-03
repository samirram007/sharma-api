<?php

namespace Modules\State\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\State\Contracts\StateRepositoryInterface;

class StateRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StateRepositoryInterface::class;
    }
}
