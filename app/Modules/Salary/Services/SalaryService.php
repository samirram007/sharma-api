<?php

namespace Modules\Salary\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Salary\Contracts\SalaryServiceInterface;
use Modules\Salary\Models\Salary;

class SalaryService extends BaseService implements SalaryServiceInterface
{
    protected string $modelClass = Salary::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Salary
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Salary
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Salary
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
