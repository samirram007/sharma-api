<?php

namespace Modules\AppModuleFeature\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\AppModuleFeature\Models\AppModuleFeature;

interface AppModuleFeatureServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?AppModuleFeature;
    public function store(array $data): AppModuleFeature;
    public function update(array $data, int $id): AppModuleFeature;
    public function delete(int $id): bool;
    public function getByRoleAndModule(int $role_id, int $module_id): Collection;
    public function getAllWithRolePermissions(int $role_id): Collection;
}
