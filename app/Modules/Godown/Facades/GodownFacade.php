<?php

namespace Modules\Godown\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Godown\Contracts\GodownServiceInterface;

class GodownFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GodownServiceInterface::class;
    }
}
