<?php

namespace Modules\StockItemPrice\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItemPrice\Contracts\StockItemPriceServiceInterface;
use Modules\StockItemPrice\Models\StockItemPrice;

class StockItemPriceService extends BaseService implements StockItemPriceServiceInterface
{
    protected string $modelClass = StockItemPrice::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockItemPrice
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockItemPrice
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockItemPrice
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
