<?php

namespace Modules\DocumentUser\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\DocumentUser\Contracts\DocumentUserServiceInterface;

class DocumentUserFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DocumentUserServiceInterface::class;
    }
}
