<?php

namespace Modules\EmployeeGroup\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\EmployeeGroup\Contracts\EmployeeGroupServiceInterface;
use Modules\EmployeeGroup\Models\EmployeeGroup;

class EmployeeGroupService extends BaseService implements EmployeeGroupServiceInterface
{
    protected string $modelClass = EmployeeGroup::class;

    protected array $defaultResource = [
        'employees',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?EmployeeGroup
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): EmployeeGroup
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): EmployeeGroup
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
