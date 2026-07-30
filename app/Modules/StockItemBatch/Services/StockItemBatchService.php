<?php

namespace Modules\StockItemBatch\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItemBatch\Contracts\StockItemBatchServiceInterface;
use Modules\StockItemBatch\Models\StockItemBatch;

class StockItemBatchService extends BaseService implements StockItemBatchServiceInterface
{
    protected string $modelClass = StockItemBatch::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockItemBatch
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockItemBatch
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockItemBatch
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
