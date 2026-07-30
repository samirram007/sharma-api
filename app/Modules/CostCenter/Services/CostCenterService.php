<?php

namespace Modules\CostCenter\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\CostCenter\Contracts\CostCenterServiceInterface;
use Modules\CostCenter\Models\CostCenter;

class CostCenterService extends BaseService implements CostCenterServiceInterface
{
    protected string $modelClass = CostCenter::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?CostCenter
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): CostCenter
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): CostCenter
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
