<?php

namespace Modules\LeaveType\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\LeaveType\Contracts\LeaveTypeRepositoryInterface;
use Modules\LeaveType\Models\LeaveType;

class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
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

    public function __construct(LeaveType $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
