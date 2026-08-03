<?php

namespace Modules\Employee\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Employee\Contracts\EmployeeRepositoryInterface;
use Modules\Employee\Models\Employee;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    /**
     * Fields that can be searched via the search() method.
     */
    protected array $searchableFields = [
        'name',
        'code',
        // 'dob',
        // 'doj',
        'email',
        // 'contact_no',
        // 'education',
        // 'pan',
        // 'image',
    ];

    /**
     * Fields that can be filtered via the filter() method.
     */
    protected array $filterableFields = [
        // 'department_id',
        // 'designation_id',
        // 'employee_group_id',
        // 'shift_id',
        // 'grade_id',
        'status',
    ];

    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }
}
