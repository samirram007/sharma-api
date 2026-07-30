<?php

namespace Modules\Uqc\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Uqc\Contracts\UqcServiceInterface;
use Modules\Uqc\Models\Uqc;

class UqcService extends BaseService implements UqcServiceInterface
{
    protected string $modelClass = Uqc::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Uqc
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Uqc
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Uqc
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
