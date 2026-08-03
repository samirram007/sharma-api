<?php

namespace Modules\AppModule\Services;

use App\Support\Services\BaseService;
use Modules\AppModule\Contracts\AppModuleServiceInterface;
use Modules\AppModule\Facades\AppModuleRepositoryFacade;
use Modules\AppModule\Models\AppModule;

class AppModuleService extends BaseService implements AppModuleServiceInterface
{
    protected string $modelClass = AppModule::class;

    protected array $defaultResource = [
        'app_module_features',
    ];

    protected string $repositoryFacadeClass = AppModuleRepositoryFacade::class;

    public function __construct() {}
}
