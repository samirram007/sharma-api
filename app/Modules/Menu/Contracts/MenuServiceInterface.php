<?php

namespace Modules\Menu\Contracts;

use Modules\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Collection;


interface MenuServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?Menu;
    public function store(array $data): Menu;
    public function update(array $data, int $id): Menu;
    public function delete(int $id): bool;
    public function getTree(): Collection;
    public function reorder(array $items): bool;

    /** Get allowed feature codes for the authenticated user. */
    public function getUserMenuPermissions(): array;

    /** Get hierarchical menu tree filtered by the user's role permissions. */
    public function getUserMenuTree(): array;

    /** Get all features with their permission status for a given role. */
    public function getRoleMenuPermissions(int $roleId): Collection;
}
