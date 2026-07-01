<?php

namespace Modules\Payment\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\Models\Payment;

interface PaymentServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?Payment;
    public function store(array $data): Payment;
    public function update(array $data, int $id): Payment;
    public function delete(int $id): bool;
    public function getPaymentsByFreightId(int $freight_id): Collection;
}
