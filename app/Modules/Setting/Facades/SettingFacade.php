<?php

namespace Modules\Setting\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Setting\Contracts\SettingServiceInterface;

class SettingFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingServiceInterface::class;
    }
}
