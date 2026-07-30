<?php

namespace Modules\DocumentUser\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\DocumentUser\Contracts\DocumentUserServiceInterface;
use Modules\DocumentUser\Models\DocumentUser;

class DocumentUserService extends BaseService implements DocumentUserServiceInterface
{
    protected string $modelClass = DocumentUser::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?DocumentUser
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): DocumentUser
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): DocumentUser
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
