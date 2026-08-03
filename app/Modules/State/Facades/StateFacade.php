<?php

namespace Modules\State\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\State\Contracts\StateServiceInterface;

class StateFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StateServiceInterface::class;
    }
}
