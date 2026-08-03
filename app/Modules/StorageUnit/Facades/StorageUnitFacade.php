<?php

namespace Modules\StorageUnit\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StorageUnit\Contracts\StorageUnitServiceInterface;

class StorageUnitFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StorageUnitServiceInterface::class;
    }
}
