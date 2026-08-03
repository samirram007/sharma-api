<?php

namespace Modules\Agent\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Agent\Contracts\AgentRepositoryInterface;

class AgentRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AgentRepositoryInterface::class;
    }
}
