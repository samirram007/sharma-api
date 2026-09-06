<?php

namespace Modules\Faq\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Faq\Contracts\FaqRepositoryInterface;

class FaqRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FaqRepositoryInterface::class;
    }
}
