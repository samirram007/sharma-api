<?php

namespace Modules\DeliveryVehicle\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\DeliveryVehicle\Contracts\DeliveryVehicleServiceInterface;
use Modules\DeliveryVehicle\Models\DeliveryVehicle;

class DeliveryVehicleService extends BaseService implements DeliveryVehicleServiceInterface
{
    protected string $modelClass = DeliveryVehicle::class;

    protected array $defaultResource = [
        'transporter',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?DeliveryVehicle
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): DeliveryVehicle
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): DeliveryVehicle
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
