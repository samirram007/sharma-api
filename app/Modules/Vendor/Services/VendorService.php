<?php

namespace Modules\Vendor\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Vendor\Contracts\VendorServiceInterface;
use Modules\Vendor\Models\Vendor;

class VendorService implements VendorServiceInterface
{
    protected $resource = [];

    public function getAll(): Collection
    {
        return Vendor::with($this->resource)->get();
    }

    public function getById(int $id): ?Vendor
    {
        return Vendor::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Vendor
    {
        return Vendor::create($data);
    }

    public function update(array $data, int $id): Vendor
    {
        $record = Vendor::findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Vendor::findOrFail($id);

        return $record->delete();
    }
}
