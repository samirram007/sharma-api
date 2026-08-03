<?php

namespace Modules\RolePermission\Services;

use App\Support\Services\BaseService;
use Modules\RolePermission\Contracts\RolePermissionServiceInterface;
use Modules\RolePermission\Facades\RolePermissionRepositoryFacade;
use Modules\RolePermission\Models\RolePermission;

class RolePermissionService extends BaseService implements RolePermissionServiceInterface
{
    protected string $modelClass = RolePermission::class;

    protected array $defaultResource = [
        'role',
        'feature.module',
    ];

    protected string $repositoryFacadeClass = RolePermissionRepositoryFacade::class;

    public function __construct() {}
}
