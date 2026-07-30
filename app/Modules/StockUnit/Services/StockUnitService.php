<?php

namespace Modules\StockUnit\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockUnit\Contracts\StockUnitRepositoryInterface;
use Modules\StockUnit\Contracts\StockUnitServiceInterface;
use Modules\StockUnit\Models\StockUnit;

class StockUnitService extends BaseService implements StockUnitServiceInterface
{
    protected string $modelClass = StockUnit::class;

    protected array $defaultResource = [
        'primary_stock_unit',
        'secondary_stock_unit',
        'unique_quantity_code',
    ];

    public function __construct(
        protected StockUnitRepositoryInterface $stockUnitRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockUnit
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockUnit
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockUnit
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
