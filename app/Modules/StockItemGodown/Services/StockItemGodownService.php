<?php

namespace Modules\StockItemGodown\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItemGodown\Contracts\StockItemGodownServiceInterface;
use Modules\StockItemGodown\Models\StockItemGodown;

class StockItemGodownService extends BaseService implements StockItemGodownServiceInterface
{
    protected string $modelClass = StockItemGodown::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockItemGodown
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockItemGodown
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockItemGodown
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
