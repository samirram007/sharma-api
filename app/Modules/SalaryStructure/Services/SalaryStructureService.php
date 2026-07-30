<?php

namespace Modules\SalaryStructure\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\SalaryStructure\Contracts\SalaryStructureServiceInterface;
use Modules\SalaryStructure\Models\SalaryStructure;

class SalaryStructureService extends BaseService implements SalaryStructureServiceInterface
{
    protected string $modelClass = SalaryStructure::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?SalaryStructure
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): SalaryStructure
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): SalaryStructure
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
