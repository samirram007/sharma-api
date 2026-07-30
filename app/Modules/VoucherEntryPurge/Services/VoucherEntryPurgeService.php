<?php

namespace Modules\VoucherEntryPurge\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherEntryPurge\Contracts\VoucherEntryPurgeServiceInterface;
use Modules\VoucherEntryPurge\Models\VoucherEntryPurge;

class VoucherEntryPurgeService extends BaseService implements VoucherEntryPurgeServiceInterface
{
    protected string $modelClass = VoucherEntryPurge::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherEntryPurge
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherEntryPurge
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherEntryPurge
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
