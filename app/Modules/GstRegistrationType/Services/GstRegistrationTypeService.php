<?php

namespace Modules\GstRegistrationType\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\GstRegistrationType\Contracts\GstRegistrationTypeServiceInterface;
use Modules\GstRegistrationType\Models\GstRegistrationType;

class GstRegistrationTypeService extends BaseService implements GstRegistrationTypeServiceInterface
{
    protected string $modelClass = GstRegistrationType::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?GstRegistrationType
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): GstRegistrationType
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): GstRegistrationType
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
