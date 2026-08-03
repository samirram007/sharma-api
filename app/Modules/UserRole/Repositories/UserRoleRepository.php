<?php

namespace Modules\UserRole\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\UserRole\Contracts\UserRoleRepositoryInterface;
use Modules\UserRole\Models\UserRole;

class UserRoleRepository extends BaseRepository implements UserRoleRepositoryInterface
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
        // 'user_id',
        // 'role_id',
    ];

    public function __construct(UserRole $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
