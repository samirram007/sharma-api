<?php

namespace Modules\AppModuleFeature\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

interface AppModuleFeatureServiceInterface extends BaseServiceInterface
{
    public function getByRoleAndModule(int $role_id, int $module_id): Collection;

    public function getAllWithRolePermissions(int $role_id): Collection;
}
