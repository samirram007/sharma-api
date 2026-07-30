<?php

namespace Modules\Role\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\Role\Contracts\RoleServiceInterface;
use Modules\Role\Models\Role;

class RoleService extends BaseService implements RoleServiceInterface
{
    protected string $modelClass = Role::class;

    protected array $defaultResource = [
        'permissions.feature.module',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?Role
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): Role
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): Role
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
