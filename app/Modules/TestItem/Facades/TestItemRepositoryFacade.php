<?php

namespace Modules\TestItem\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\TestItem\Contracts\TestItemRepositoryInterface;

class TestItemRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TestItemRepositoryInterface::class;
    }
}
