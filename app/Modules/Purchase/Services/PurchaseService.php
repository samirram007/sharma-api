<?php

namespace Modules\Purchase\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Purchase\Contracts\PurchaseServiceInterface;
use Modules\Purchase\Models\Purchase;

class PurchaseService implements PurchaseServiceInterface
{
    protected $resource = [];

    public function getAll(): Collection
    {
        return Purchase::with($this->resource)->get();
    }

    public function getById(int $id): ?Purchase
    {
        return Purchase::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Purchase
    {
        return Purchase::create($data);
    }

    public function update(array $data, int $id): Purchase
    {
        $record = Purchase::findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Purchase::findOrFail($id);

        return $record->delete();
    }
}
