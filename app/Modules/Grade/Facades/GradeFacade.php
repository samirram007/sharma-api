<?php

namespace Modules\Grade\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Grade\Contracts\GradeServiceInterface;

class GradeFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GradeServiceInterface::class;
    }
}
