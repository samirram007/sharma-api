<?php

namespace Modules\Department\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Department\Contracts\DepartmentRepositoryInterface;
use Modules\Department\Contracts\DepartmentServiceInterface;
use Modules\Department\Models\Department;

class DepartmentService extends BaseService implements DepartmentServiceInterface
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository
    ) {}

    protected string $modelClass = Department::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Department
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Department
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Department
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
