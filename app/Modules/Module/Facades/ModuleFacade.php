<?php

namespace Modules\Module\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Module\Contracts\ModuleServiceInterface;

class ModuleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleServiceInterface::class;
    }
}
