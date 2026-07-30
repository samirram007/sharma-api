<?php

namespace Modules\UniqueQuantityCode\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\UniqueQuantityCode\Contracts\UniqueQuantityCodeServiceInterface;
use Modules\UniqueQuantityCode\Models\UniqueQuantityCode;

class UniqueQuantityCodeService extends BaseService implements UniqueQuantityCodeServiceInterface
{
    protected string $modelClass = UniqueQuantityCode::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?UniqueQuantityCode
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): UniqueQuantityCode
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): UniqueQuantityCode
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
