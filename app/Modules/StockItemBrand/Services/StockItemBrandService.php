<?php

namespace Modules\StockItemBrand\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItemBrand\Contracts\StockItemBrandServiceInterface;
use Modules\StockItemBrand\Models\StockItemBrand;

class StockItemBrandService extends BaseService implements StockItemBrandServiceInterface
{
    protected string $modelClass = StockItemBrand::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockItemBrand
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockItemBrand
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockItemBrand
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
