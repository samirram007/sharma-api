<?php

namespace Modules\StockCategory\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\StockCategory\Models\StockCategory;

interface StockCategoryServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?StockCategory;

    public function store(array $data): StockCategory;

    public function update(array $data, int $id): StockCategory;

    public function delete(int $id): bool;
}
