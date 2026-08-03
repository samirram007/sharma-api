<?php

namespace Modules\Role\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Role\Contracts\RoleRepositoryInterface;
use Modules\Role\Models\Role;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
