<?php

namespace Modules\Branch\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Branch\Models\Branch;

interface BranchServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?Branch;
    public function store(array $data): Branch;
    public function update(array $data, int $id): Branch;
    public function delete(int $id): bool;
}
