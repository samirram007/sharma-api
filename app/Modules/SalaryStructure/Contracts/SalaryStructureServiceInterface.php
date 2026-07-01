<?php

namespace Modules\SalaryStructure\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\SalaryStructure\Models\SalaryStructure;

interface SalaryStructureServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?SalaryStructure;
    public function store(array $data): SalaryStructure;
    public function update(array $data, int $id): SalaryStructure;
    public function delete(int $id): bool;
}
