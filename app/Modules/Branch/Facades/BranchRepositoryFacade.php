<?php

namespace Modules\Branch\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Branch\Contracts\BranchRepositoryInterface;

class BranchRepositoryFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BranchRepositoryInterface::class;
    }
}
