<?php

namespace Modules\Grade\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Grade\Contracts\GradeServiceInterface;
use Modules\Grade\Models\Grade;

class GradeService extends BaseService implements GradeServiceInterface
{
    protected string $modelClass = Grade::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Grade
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Grade
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Grade
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
