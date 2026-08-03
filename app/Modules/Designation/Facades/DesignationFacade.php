<?php

namespace Modules\Designation\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Designation\Contracts\DesignationServiceInterface;

class DesignationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DesignationServiceInterface::class;
    }
}
