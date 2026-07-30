<?php

namespace Modules\StockItem\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItem\Contracts\StockItemServiceInterface;
use Modules\StockItem\Models\StockItem;

class StockItemService extends BaseService implements StockItemServiceInterface
{
    protected string $modelClass = StockItem::class;

    protected array $defaultResource = ['stock_unit', 'alternate_stock_unit'];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockItem
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockItem
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockItem
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }

    public function getPurchasableStockItems(): Collection
    {
        return $this->queryWithResource()->get();
    }
}
