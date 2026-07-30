<?php

namespace Modules\Module\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Module\Contracts\ModuleServiceInterface;
use Modules\Module\Models\Module;

class ModuleService extends BaseService implements ModuleServiceInterface
{
    protected string $modelClass = Module::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Module
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Module
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Module
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
