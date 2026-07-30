<?php

namespace Modules\Holiday\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Holiday\Contracts\HolidayServiceInterface;
use Modules\Holiday\Models\Holiday;

class HolidayService extends BaseService implements HolidayServiceInterface
{
    protected string $modelClass = Holiday::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Holiday
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Holiday
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Holiday
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
