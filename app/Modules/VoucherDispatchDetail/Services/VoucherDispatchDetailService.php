<?php

namespace Modules\VoucherDispatchDetail\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;

class VoucherDispatchDetailService extends BaseService implements VoucherDispatchDetailServiceInterface
{
    protected string $modelClass = VoucherDispatchDetail::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherDispatchDetail
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherDispatchDetail
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherDispatchDetail
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
