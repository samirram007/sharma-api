<?php

namespace Modules\Distributor\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Distributor\Contracts\DistributorServiceInterface;

class DistributorFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DistributorServiceInterface::class;
    }
}
