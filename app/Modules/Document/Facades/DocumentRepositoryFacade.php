<?php

namespace Modules\Document\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Document\Contracts\DocumentRepositoryInterface;

class DocumentRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DocumentRepositoryInterface::class;
    }
}
