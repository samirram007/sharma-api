<?php

namespace Modules\Country\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Country\Contracts\CountryServiceInterface;

class CountryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CountryServiceInterface::class;
    }
}
