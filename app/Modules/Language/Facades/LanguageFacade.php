<?php

namespace Modules\Language\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Language\Contracts\LanguageServiceInterface;

class LanguageFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LanguageServiceInterface::class;
    }
}
