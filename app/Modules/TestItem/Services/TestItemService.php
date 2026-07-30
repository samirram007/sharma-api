<?php

namespace Modules\TestItem\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\TestItem\Contracts\TestItemServiceInterface;
use Modules\TestItem\Models\TestItem;

class TestItemService extends BaseService implements TestItemServiceInterface
{
    protected string $modelClass = TestItem::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?TestItem
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): TestItem
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): TestItem
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
