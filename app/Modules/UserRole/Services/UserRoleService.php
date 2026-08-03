<?php

namespace Modules\UserRole\Services;

use App\Support\Services\BaseService;
use Modules\UserRole\Contracts\UserRoleServiceInterface;
use Modules\UserRole\Facades\UserRoleRepositoryFacade;
use Modules\UserRole\Models\UserRole;

class UserRoleService extends BaseService implements UserRoleServiceInterface
{
    protected string $modelClass = UserRole::class;

    protected string $repositoryFacadeClass = UserRoleRepositoryFacade::class;

    public function __construct() {}
}
