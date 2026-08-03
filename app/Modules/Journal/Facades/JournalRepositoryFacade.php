<?php

namespace Modules\Journal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Journal\Contracts\JournalRepositoryInterface;

class JournalRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return JournalRepositoryInterface::class;
    }
}
