<?php

namespace Modules\DayBook\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DayBook\Contracts\DayBookServiceInterface;

class DayBookFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DayBookServiceInterface::class;
    }
}
