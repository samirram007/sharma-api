<?php

namespace Modules\Purchase\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Purchase\Models\Purchase;

interface PurchaseServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?Purchase;
    public function store(array $data): Purchase;
    public function update(array $data, int $id): Purchase;
    public function delete(int $id): bool;
}
