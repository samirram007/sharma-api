<?php

namespace Modules\AppModuleFeature\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\AppModuleFeature\Contracts\AppModuleFeatureServiceInterface;

class AppModuleFeatureFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AppModuleFeatureServiceInterface::class;
    }
}
