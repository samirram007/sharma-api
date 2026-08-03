<?php

namespace Modules\Agent\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Agent\Contracts\AgentServiceInterface;

class AgentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AgentServiceInterface::class;
    }
}
