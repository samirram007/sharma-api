<?php

namespace Modules\Uqc\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Uqc\Contracts\UqcServiceInterface;

class UqcFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UqcServiceInterface::class;
    }
}
