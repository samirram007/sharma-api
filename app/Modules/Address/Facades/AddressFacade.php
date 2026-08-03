<?php

namespace Modules\Address\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Address\Contracts\AddressServiceInterface;

class AddressFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddressServiceInterface::class;
    }
}
