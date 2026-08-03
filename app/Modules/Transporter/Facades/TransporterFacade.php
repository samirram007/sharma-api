<?php

namespace Modules\Transporter\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Transporter\Contracts\TransporterServiceInterface;

class TransporterFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TransporterServiceInterface::class;
    }
}
