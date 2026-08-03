<?php

namespace Modules\DocumentUser\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DocumentUser\Contracts\DocumentUserRepositoryInterface;

class DocumentUserRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DocumentUserRepositoryInterface::class;
    }
}
