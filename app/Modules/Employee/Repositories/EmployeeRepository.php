<?php

namespace Modules\Employee\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Employee\Contracts\EmployeeRepositoryInterface;
use Modules\Employee\Models\Employee;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }
}
