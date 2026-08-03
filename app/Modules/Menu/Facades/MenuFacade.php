<?php

namespace Modules\Menu\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Menu\Contracts\MenuServiceInterface;

class MenuFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MenuServiceInterface::class;
    }
}
