<?php

namespace Modules\LeaveType\Services;

use Modules\LeaveType\Contracts\LeaveTypeServiceInterface;
use Modules\LeaveType\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;

class LeaveTypeService implements LeaveTypeServiceInterface
{
    protected $resource=[];

    public function getAll(): Collection
    {
        return LeaveType::with($this->resource)->get();
    }

    public function getById(int $id): ?LeaveType
    {
        return LeaveType::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): LeaveType
    {
        return LeaveType::create($data);
    }

    public function update(array $data, int $id): LeaveType
    {
        $record = LeaveType::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = LeaveType::findOrFail($id);
        return $record->delete();
    }
}
