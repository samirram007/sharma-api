<?php

namespace Modules\UniqueQuantityCode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeRepositoryInterface;

class UniqueQuantityCodeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UniqueQuantityCodeRepositoryInterface::class;
    }
}
