<?php

namespace Modules\Document\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Document\Contracts\DocumentServiceInterface;

class DocumentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DocumentServiceInterface::class;
    }
}
