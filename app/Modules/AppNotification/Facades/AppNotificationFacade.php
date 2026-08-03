<?php

namespace Modules\AppNotification\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppNotification\Contracts\AppNotificationServiceInterface;

class AppNotificationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppNotificationServiceInterface::class;
    }
}
