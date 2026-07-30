<?php

namespace Modules\Department\Repositories;

use App\Support\Repositories\BaseRepository;
use Modules\Department\Contracts\DepartmentRepositoryInterface;
use Modules\Department\Models\Department;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }
}
