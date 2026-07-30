<?php

namespace Modules\DeliveryRoute\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\DeliveryRoute\Contracts\DeliveryRouteServiceInterface;
use Modules\DeliveryRoute\Models\DeliveryRoute;

class DeliveryRouteService extends BaseService implements DeliveryRouteServiceInterface
{
    protected string $modelClass = DeliveryRoute::class;

    protected array $defaultResource = [
        'source_place',
        'destination_place',
        'transporter',
        'rate_unit',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?DeliveryRoute
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): DeliveryRoute
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): DeliveryRoute
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
