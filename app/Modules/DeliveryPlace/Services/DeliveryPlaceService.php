<?php

namespace Modules\DeliveryPlace\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\DeliveryPlace\Contracts\DeliveryPlaceServiceInterface;
use Modules\DeliveryPlace\Models\DeliveryPlace;

class DeliveryPlaceService extends BaseService implements DeliveryPlaceServiceInterface
{
    protected string $modelClass = DeliveryPlace::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?DeliveryPlace
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): DeliveryPlace
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): DeliveryPlace
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
