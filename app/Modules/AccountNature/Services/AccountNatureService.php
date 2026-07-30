<?php

namespace Modules\AccountNature\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AccountNature\Contracts\AccountNatureServiceInterface;
use Modules\AccountNature\Models\AccountNature;

class AccountNatureService extends BaseService implements AccountNatureServiceInterface
{
    protected string $modelClass = AccountNature::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?AccountNature
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): AccountNature
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): AccountNature
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
