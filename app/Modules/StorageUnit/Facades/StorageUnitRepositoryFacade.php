<?php

namespace Modules\StorageUnit\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\StorageUnit\Contracts\StorageUnitRepositoryInterface;

class StorageUnitRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StorageUnitRepositoryInterface::class;
    }
}
