<?php

namespace Modules\Faq\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Faq\Contracts\FaqServiceInterface;

class FaqFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FaqServiceInterface::class;
    }
}
