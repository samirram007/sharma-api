<?php

namespace Modules\AppModule\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppModule\Contracts\AppModuleRepositoryInterface;

class AppModuleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppModuleRepositoryInterface::class;
    }
}
