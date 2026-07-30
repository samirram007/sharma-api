<?php

namespace Modules\CostCategory\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\CostCategory\Contracts\CostCategoryServiceInterface;
use Modules\CostCategory\Models\CostCategory;

class CostCategoryService extends BaseService implements CostCategoryServiceInterface
{
    protected string $modelClass = CostCategory::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?CostCategory
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): CostCategory
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): CostCategory
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
