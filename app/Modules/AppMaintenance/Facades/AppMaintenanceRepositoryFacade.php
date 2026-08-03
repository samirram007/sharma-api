<?php

namespace Modules\AppMaintenance\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppMaintenance\Contracts\AppMaintenanceRepositoryInterface;

class AppMaintenanceRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppMaintenanceRepositoryInterface::class;
    }
}
