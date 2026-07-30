<?php

namespace Modules\Status\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Status\Contracts\StatusServiceInterface;
use Modules\Status\Models\Status;

class StatusService extends BaseService implements StatusServiceInterface
{
    protected string $modelClass = Status::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Status
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Status
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Status
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
