<?php

namespace Modules\Shift\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Shift\Contracts\ShiftServiceInterface;

class ShiftFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShiftServiceInterface::class;
    }
}
