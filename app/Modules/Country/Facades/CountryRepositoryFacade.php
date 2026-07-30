<?php

namespace Modules\Country\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Country\Contracts\CountryRepositoryInterface;

class CountryRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CountryRepositoryInterface::class;
    }
}
