<?php

namespace Modules\Designation\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Designation\Contracts\DesignationServiceInterface;
use Modules\Designation\Models\Designation;

class DesignationService extends BaseService implements DesignationServiceInterface
{
    protected string $modelClass = Designation::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Designation
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Designation
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Designation
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
