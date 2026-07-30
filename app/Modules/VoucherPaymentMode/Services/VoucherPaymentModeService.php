<?php

namespace Modules\VoucherPaymentMode\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherPaymentMode\Contracts\VoucherPaymentModeServiceInterface;
use Modules\VoucherPaymentMode\Models\VoucherPaymentMode;

class VoucherPaymentModeService extends BaseService implements VoucherPaymentModeServiceInterface
{
    protected string $modelClass = VoucherPaymentMode::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherPaymentMode
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherPaymentMode
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherPaymentMode
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
