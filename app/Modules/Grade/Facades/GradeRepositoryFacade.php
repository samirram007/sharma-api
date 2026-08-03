<?php

namespace Modules\Grade\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Grade\Contracts\GradeRepositoryInterface;

class GradeRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GradeRepositoryInterface::class;
    }
}
