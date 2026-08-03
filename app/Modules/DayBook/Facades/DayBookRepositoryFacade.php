<?php

namespace Modules\DayBook\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DayBook\Contracts\DayBookRepositoryInterface;

class DayBookRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DayBookRepositoryInterface::class;
    }
}
