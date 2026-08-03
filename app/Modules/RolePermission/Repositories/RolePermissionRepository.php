<?php

namespace Modules\RolePermission\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\RolePermission\Contracts\RolePermissionRepositoryInterface;
use Modules\RolePermission\Models\RolePermission;

class RolePermissionRepository extends BaseRepository implements RolePermissionRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'role_id',
        // 'app_module_feature_id',
        // 'is_allowed',
    ];

    public function __construct(RolePermission $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
