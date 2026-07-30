<?php

namespace Modules\Shift\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shift\Contracts\ShiftServiceInterface;
use Modules\Shift\Models\Shift;

class ShiftService extends BaseService implements ShiftServiceInterface
{
    protected string $modelClass = Shift::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Shift
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Shift
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Shift
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
