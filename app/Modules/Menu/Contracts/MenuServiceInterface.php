<?php

namespace Modules\Menu\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Menu\Models\Menu;

interface MenuServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?Menu;

    public function store(array $data): Menu;

    public function update(array $data, int $id): Menu;

    public function delete(int $id): bool;

    public function getTree(): Collection;

    public function reorder(array $items): bool;

    public function batchUpdate(array $ids, array $data): int;

    public function batchDelete(array $ids): int;

    public function duplicate(int $id): Menu;

    public function exportJson(): array;

    public function importJson(array $items): int;

    public function search(string $query, int $perPage = 20): LengthAwarePaginator;

    /** Get allowed feature codes for the authenticated user. */
    public function getUserMenuPermissions(): array;

    /** Get hierarchical menu tree filtered by the user's role permissions. */
    public function getUserMenuTree(): array;

    /** Get all features with their permission status for a given role. */
    public function getRoleMenuPermissions(int $roleId): Collection;
}
