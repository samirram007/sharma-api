<?php

namespace Modules\HsnSacCode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\HsnSacCode\Contracts\HsnSacCodeRepositoryInterface;

class HsnSacCodeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HsnSacCodeRepositoryInterface::class;
    }
}
