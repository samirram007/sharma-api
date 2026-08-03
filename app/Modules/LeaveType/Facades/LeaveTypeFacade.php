<?php

namespace Modules\LeaveType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\LeaveType\Contracts\LeaveTypeServiceInterface;

class LeaveTypeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LeaveTypeServiceInterface::class;
    }
}
