<?php

namespace Modules\DistributorBook\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DistributorBook\Contracts\DistributorBookServiceInterface;

class DistributorBookFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DistributorBookServiceInterface::class;
    }
}
