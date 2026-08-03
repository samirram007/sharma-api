<?php

namespace Modules\Shift\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Shift\Contracts\ShiftRepositoryInterface;

class ShiftRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShiftRepositoryInterface::class;
    }
}
