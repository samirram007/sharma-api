<?php

namespace Modules\Holiday\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Holiday\Contracts\HolidayRepositoryInterface;

class HolidayRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HolidayRepositoryInterface::class;
    }
}
