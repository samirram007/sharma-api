<?php

namespace Modules\VoucherReference\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherReference\Models\VoucherReference;

interface VoucherReferenceServiceInterface
{
    public function getAll(): Collection;

    public function getByVoucherId(int $voucherId): Collection;

    public function getByReferenceVoucherId(int $voucherId): Collection;

    public function getById(int $id): ?VoucherReference;

    public function store(array $data): VoucherReference;

    public function update(array $data, int $id): VoucherReference;

    public function delete(int $id): bool;
}
