<?php

namespace Modules\Company\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Company\Models\Company;

interface CompanyServiceInterface
{
    public function getAll(?string $status = null): Collection;
    public function getById(int $id): ?Company;
    public function store(array $data): Company;
    public function update(array $data, int $id): Company;
    public function delete(int $id): bool;
}
