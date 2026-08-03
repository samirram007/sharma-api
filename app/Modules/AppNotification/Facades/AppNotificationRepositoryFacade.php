<?php

namespace Modules\AppNotification\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppNotification\Contracts\AppNotificationRepositoryInterface;

class AppNotificationRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppNotificationRepositoryInterface::class;
    }
}
