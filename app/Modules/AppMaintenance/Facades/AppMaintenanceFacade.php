<?php

namespace Modules\AppMaintenance\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppMaintenance\Contracts\AppMaintenanceServiceInterface;

class AppMaintenanceFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppMaintenanceServiceInterface::class;
    }
}
