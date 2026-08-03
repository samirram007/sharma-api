<?php

namespace Modules\GstRegistrationType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeRepositoryInterface;

class GstRegistrationTypeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GstRegistrationTypeRepositoryInterface::class;
    }
}
