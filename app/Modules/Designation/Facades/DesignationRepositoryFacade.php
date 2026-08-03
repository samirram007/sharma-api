<?php

namespace Modules\Designation\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Designation\Contracts\DesignationRepositoryInterface;

class DesignationRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DesignationRepositoryInterface::class;
    }
}
