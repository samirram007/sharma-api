<?php

namespace Modules\StorageUnit\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StorageUnit\Contracts\StorageUnitServiceInterface;
use Modules\StorageUnit\Models\StorageUnit;

class StorageUnitService extends BaseService implements StorageUnitServiceInterface
{
    protected string $modelClass = StorageUnit::class;

    protected array $defaultResource = [
        'parent',
        'capacity_unit',
        'address',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StorageUnit
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StorageUnit
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StorageUnit
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
