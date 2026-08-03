<?php

namespace Modules\DistributorBook\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DistributorBook\Contracts\DistributorBookRepositoryInterface;

class DistributorBookRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DistributorBookRepositoryInterface::class;
    }
}
