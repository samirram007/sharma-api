<?php

namespace Modules\AppModule\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppModule\Contracts\AppModuleServiceInterface;

class AppModuleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppModuleServiceInterface::class;
    }
}
