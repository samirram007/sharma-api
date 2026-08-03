<?php

namespace Modules\Uqc\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Uqc\Contracts\UqcRepositoryInterface;

class UqcRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UqcRepositoryInterface::class;
    }
}
