<?php

namespace Modules\VoucherEntry\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherEntry\Contracts\VoucherEntryServiceInterface;
use Modules\VoucherEntry\Models\VoucherEntry;

class VoucherEntryService extends BaseService implements VoucherEntryServiceInterface
{
    protected string $modelClass = VoucherEntry::class;

    protected array $defaultResource = [
        'account_ledger',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherEntry
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherEntry
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherEntry
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
