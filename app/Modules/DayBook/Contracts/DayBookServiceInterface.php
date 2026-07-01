<?php

namespace Modules\DayBook\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\DayBook\Models\DayBook;
use Illuminate\Pagination\LengthAwarePaginator;

interface DayBookServiceInterface
{
    public function getAll(array $params = []): LengthAwarePaginator;
    public function dayBooksSelf(array $params = []): LengthAwarePaginator;
    public function getUsedVoucherTypes(): Collection;

    public function getById(int $id): ?DayBook;
    public function store(array $data): DayBook;
    public function update(array $data, int $id): DayBook;
    public function delete(int $id): bool;
}
