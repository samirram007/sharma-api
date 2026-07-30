<?php

namespace Modules\StockGroup\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockGroup\Contracts\StockGroupRepositoryInterface;
use Modules\StockGroup\Contracts\StockGroupServiceInterface;
use Modules\StockGroup\Models\StockGroup;

class StockGroupService extends BaseService implements StockGroupServiceInterface
{
    protected string $modelClass = StockGroup::class;

    protected array $defaultResource = [
        'parent',
    ];

    public function __construct(
        protected StockGroupRepositoryInterface $stockGroupRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockGroup
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockGroup
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockGroup
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
