<?php

namespace Modules\UniqueQuantityCode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeServiceInterface;

class UniqueQuantityCodeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UniqueQuantityCodeServiceInterface::class;
    }
}
