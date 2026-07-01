<?php

namespace Modules\Supplier\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Supplier\Models\Supplier;

interface SupplierServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?Supplier;
    public function store(array $data): Supplier;
    public function update(array $data, int $id): Supplier;
    public function delete(int $id): bool;
}
