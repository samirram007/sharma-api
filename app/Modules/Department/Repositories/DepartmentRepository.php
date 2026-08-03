<?php

namespace Modules\Department\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Department\Contracts\DepartmentRepositoryInterface;
use Modules\Department\Models\Department;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
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

    public function __construct(Department $model)
    {
        parent::__construct($model);
    }
}
