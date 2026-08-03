<?php

namespace Modules\Language\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Language\Contracts\LanguageRepositoryInterface;

class LanguageRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LanguageRepositoryInterface::class;
    }
}
