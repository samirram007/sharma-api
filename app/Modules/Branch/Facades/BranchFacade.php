<?php

namespace Modules\Branch\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Branch\Contracts\BranchServiceInterface;

class BranchFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BranchServiceInterface::class;
    }
}
