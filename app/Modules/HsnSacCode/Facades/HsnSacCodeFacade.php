<?php

namespace Modules\HsnSacCode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\HsnSacCode\Contracts\HsnSacCodeServiceInterface;

class HsnSacCodeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HsnSacCodeServiceInterface::class;
    }
}
