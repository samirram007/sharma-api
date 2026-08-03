<?php

namespace Modules\Role\Services;

use App\Support\Services\BaseService;
use Modules\Role\Contracts\RoleServiceInterface;
use Modules\Role\Facades\RoleRepositoryFacade;
use Modules\Role\Models\Role;

class RoleService extends BaseService implements RoleServiceInterface
{
    protected string $modelClass = Role::class;

    protected array $defaultResource = [
        'permissions.feature.module',
    ];

    protected string $repositoryFacadeClass = RoleRepositoryFacade::class;

    public function __construct() {}
}
