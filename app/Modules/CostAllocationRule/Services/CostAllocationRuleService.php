<?php

namespace Modules\CostAllocationRule\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\CostAllocationRule\Contracts\CostAllocationRuleServiceInterface;
use Modules\CostAllocationRule\Models\CostAllocationRule;

class CostAllocationRuleService extends BaseService implements CostAllocationRuleServiceInterface
{
    protected string $modelClass = CostAllocationRule::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?CostAllocationRule
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): CostAllocationRule
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): CostAllocationRule
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
