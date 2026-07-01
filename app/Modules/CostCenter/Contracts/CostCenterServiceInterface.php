<?php

namespace Modules\CostCenter\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\CostCenter\Models\CostCenter;

interface CostCenterServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?CostCenter;
    public function store(array $data): CostCenter;
    public function update(array $data, int $id): CostCenter;
    public function delete(int $id): bool;
}
