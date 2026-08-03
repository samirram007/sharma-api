<?php

namespace Modules\GstRegistrationType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeServiceInterface;

class GstRegistrationTypeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GstRegistrationTypeServiceInterface::class;
    }
}
