<?php

namespace Modules\Module\Services;

use App\Support\Services\BaseService;
use Modules\Module\Contracts\ModuleServiceInterface;
use Modules\Module\Facades\ModuleRepositoryFacade;
use Modules\Module\Models\Module;

class ModuleService extends BaseService implements ModuleServiceInterface
{
    protected string $modelClass = Module::class;

    protected string $repositoryFacadeClass = ModuleRepositoryFacade::class;

    public function __construct() {}
}
