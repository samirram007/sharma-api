<?php

namespace Modules\Holiday\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Holiday\Contracts\HolidayServiceInterface;

class HolidayFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HolidayServiceInterface::class;
    }
}
