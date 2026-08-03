<?php

namespace Modules\Module\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Module\Contracts\ModuleRepositoryInterface;

class ModuleRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleRepositoryInterface::class;
    }
}
