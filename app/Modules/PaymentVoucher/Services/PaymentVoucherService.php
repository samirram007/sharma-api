<?php

namespace Modules\PaymentVoucher\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\PaymentVoucher\Contracts\PaymentVoucherServiceInterface;
use Modules\PaymentVoucher\Models\PaymentVoucher;

class PaymentVoucherService extends BaseService implements PaymentVoucherServiceInterface
{
    protected string $modelClass = PaymentVoucher::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?PaymentVoucher
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): PaymentVoucher
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): PaymentVoucher
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
