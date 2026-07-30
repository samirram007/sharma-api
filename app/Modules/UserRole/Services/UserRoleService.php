<?php

namespace Modules\UserRole\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\UserRole\Contracts\UserRoleServiceInterface;
use Modules\UserRole\Models\UserRole;

class UserRoleService extends BaseService implements UserRoleServiceInterface
{
    protected string $modelClass = UserRole::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?UserRole
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): UserRole
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): UserRole
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
