<?php

namespace Modules\VoucherParty\Services;

use Modules\VoucherParty\Contracts\VoucherPartyServiceInterface;
use Modules\VoucherParty\Models\VoucherParty;
use Illuminate\Database\Eloquent\Collection;

class VoucherPartyService implements VoucherPartyServiceInterface
{
    protected $resource = [];

    public function getAll(): Collection
    {
        return VoucherParty::with($this->resource)->get();
    }

    public function getById(int $id): ?VoucherParty
    {
        return VoucherParty::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): VoucherParty
    {

        return VoucherParty::create($data);
    }

    public function update(array $data, int $id): VoucherParty
    {
        $record = VoucherParty::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = VoucherParty::findOrFail($id);
        return $record->delete();
    }
}
