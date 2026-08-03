<?php

namespace Modules\Post\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Post\Contracts\PostServiceInterface;

class PostFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PostServiceInterface::class;
    }
}
