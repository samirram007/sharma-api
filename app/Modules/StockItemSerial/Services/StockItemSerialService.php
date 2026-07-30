<?php

namespace Modules\StockItemSerial\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockItemSerial\Contracts\StockItemSerialServiceInterface;
use Modules\StockItemSerial\Models\StockItemSerial;

class StockItemSerialService extends BaseService implements StockItemSerialServiceInterface
{
    protected string $modelClass = StockItemSerial::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockItemSerial
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockItemSerial
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockItemSerial
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
