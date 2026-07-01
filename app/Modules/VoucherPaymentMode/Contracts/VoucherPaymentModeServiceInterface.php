<?php

namespace Modules\VoucherPaymentMode\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherPaymentMode\Models\VoucherPaymentMode;

interface VoucherPaymentModeServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?VoucherPaymentMode;
    public function store(array $data): VoucherPaymentMode;
    public function update(array $data, int $id): VoucherPaymentMode;
    public function delete(int $id): bool;
}
