<?php

namespace Modules\AccountingPeriod\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\AccountingPeriod\Models\AccountingPeriod;

interface AccountingPeriodServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?AccountingPeriod;

    public function store(array $data): AccountingPeriod;

    public function update(array $data, int $id): AccountingPeriod;

    public function delete(int $id): bool;
}
