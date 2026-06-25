<?php

namespace App\Modules\Status\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Modules\Status\Models\Status;

interface StatusServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?Status;
    public function store(array $data): Status;
    public function update(array $data, int $id): Status;
    public function delete(int $id): bool;
}
