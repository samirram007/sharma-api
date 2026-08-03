<?php

namespace Modules\EmployeeGroup\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\EmployeeGroup\Contracts\EmployeeGroupRepositoryInterface;
use Modules\EmployeeGroup\Models\EmployeeGroup;

class EmployeeGroupRepository extends BaseRepository implements EmployeeGroupRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        'description',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        'status',
    ];

    public function __construct(EmployeeGroup $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
