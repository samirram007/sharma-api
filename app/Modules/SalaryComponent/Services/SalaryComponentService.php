<?php

namespace Modules\SalaryComponent\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\SalaryComponent\Contracts\SalaryComponentServiceInterface;
use Modules\SalaryComponent\Models\SalaryComponent;

class SalaryComponentService extends BaseService implements SalaryComponentServiceInterface
{
    protected string $modelClass = SalaryComponent::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?SalaryComponent
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): SalaryComponent
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): SalaryComponent
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
