<?php

namespace Modules\RolePermission\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\RolePermission\Contracts\RolePermissionServiceInterface;
use Modules\RolePermission\Models\RolePermission;

class RolePermissionService extends BaseService implements RolePermissionServiceInterface
{
    protected string $modelClass = RolePermission::class;

    protected array $defaultResource = [
        'role',
        'feature.module',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?RolePermission
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): RolePermission
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): RolePermission
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
