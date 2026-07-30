<?php

namespace Modules\VoucherParty\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;
use Modules\VoucherParty\Models\VoucherParty;

class VoucherPartyService extends BaseService implements VoucherPartyServiceInterface
{
    protected string $modelClass = VoucherParty::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?VoucherParty
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): VoucherParty
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): VoucherParty
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
