<?php

namespace Modules\TestItem\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\TestItem\Contracts\TestItemServiceInterface;

class TestItemFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TestItemServiceInterface::class;
    }
}
