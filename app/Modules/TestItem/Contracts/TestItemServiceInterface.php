<?php

namespace Modules\TestItem\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\TestItem\Models\TestItem;

interface TestItemServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?TestItem;

    public function store(array $data): TestItem;

    public function update(array $data, int $id): TestItem;

    public function delete(int $id): bool;
}
