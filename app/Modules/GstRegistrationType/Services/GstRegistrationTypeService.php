<?php

namespace Modules\GstRegistrationType\Services;

use Modules\GstRegistrationType\Contracts\GstRegistrationTypeServiceInterface;
use Modules\GstRegistrationType\Models\GstRegistrationType;
use Illuminate\Database\Eloquent\Collection;

class GstRegistrationTypeService implements GstRegistrationTypeServiceInterface
{
    protected $resource=[];

    public function getAll(): Collection
    {
        return GstRegistrationType::with($this->resource)->get();
    }

    public function getById(int $id): ?GstRegistrationType
    {
        return GstRegistrationType::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): GstRegistrationType
    {
        return GstRegistrationType::create($data);
    }

    public function update(array $data, int $id): GstRegistrationType
    {
        $record = GstRegistrationType::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = GstRegistrationType::findOrFail($id);
        return $record->delete();
    }
}
