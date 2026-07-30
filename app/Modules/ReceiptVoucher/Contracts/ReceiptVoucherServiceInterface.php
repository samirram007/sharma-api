<?php

namespace Modules\ReceiptVoucher\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\ReceiptVoucher\Models\ReceiptVoucher;
use Modules\Voucher\Models\Voucher;

interface ReceiptVoucherServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?ReceiptVoucher;

    public function store(array $data): ReceiptVoucher;

    public function update(array $data, int $id): ReceiptVoucher;

    public function delete(int $id): bool;

    public function getFreightReceiptByFreightId(int $freight_id): Collection;

    public function storeFreightReceiptVoucher(array $data): Voucher;
}
