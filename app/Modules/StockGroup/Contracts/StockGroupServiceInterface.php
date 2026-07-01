<?php

namespace Modules\StockGroup\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\StockGroup\Models\StockGroup;

interface StockGroupServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?StockGroup;
    public function store(array $data): StockGroup;
    public function update(array $data, int $id): StockGroup;
    public function delete(int $id): bool;
}
