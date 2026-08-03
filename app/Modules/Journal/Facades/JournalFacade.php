<?php

namespace Modules\Journal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Journal\Contracts\JournalServiceInterface;

class JournalFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JournalServiceInterface::class;
    }
}
