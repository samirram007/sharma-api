<?php

namespace Modules\Address\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Address\Contracts\AddressRepositoryInterface;

class AddressRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddressRepositoryInterface::class;
    }
}
