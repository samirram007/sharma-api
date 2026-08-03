<?php

namespace Modules\Setting\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Setting\Contracts\SettingRepositoryInterface;

class SettingRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingRepositoryInterface::class;
    }
}
