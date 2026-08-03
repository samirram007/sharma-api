<?php

namespace Modules\Post\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Post\Contracts\PostRepositoryInterface;

class PostRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PostRepositoryInterface::class;
    }
}
