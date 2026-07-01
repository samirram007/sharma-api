<?php

namespace Modules\StockItemBrand\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\StockItemBrand\Models\StockItemBrand;

interface StockItemBrandServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?StockItemBrand;
    public function store(array $data): StockItemBrand;
    public function update(array $data, int $id): StockItemBrand;
    public function delete(int $id): bool;
}
