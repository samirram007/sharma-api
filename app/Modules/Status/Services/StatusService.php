<?php

namespace App\Modules\Status\Services;

use App\Modules\Status\Contracts\StatusServiceInterface;
use App\Modules\Status\Models\Status;
use Illuminate\Database\Eloquent\Collection;

class StatusService implements StatusServiceInterface
{
    protected $resource = [];

    public function getAll(): Collection
    {
        return Status::with($this->resource)->get();
    }

    public function getById(int $id): ?Status
    {
        return Status::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Status
    {
        return Status::create($data);
    }

    public function update(array $data, int $id): Status
    {
        $record = Status::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Status::findOrFail($id);
        return $record->delete();
    }
}
