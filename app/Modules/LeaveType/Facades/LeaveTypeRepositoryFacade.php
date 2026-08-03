<?php

namespace Modules\LeaveType\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\LeaveType\Contracts\LeaveTypeRepositoryInterface;

class LeaveTypeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LeaveTypeRepositoryInterface::class;
    }
}
