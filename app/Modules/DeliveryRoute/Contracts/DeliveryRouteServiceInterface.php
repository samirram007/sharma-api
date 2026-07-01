<?php

namespace Modules\DeliveryRoute\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\DeliveryRoute\Models\DeliveryRoute;

interface DeliveryRouteServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?DeliveryRoute;
    public function store(array $data): DeliveryRoute;
    public function update(array $data, int $id): DeliveryRoute;
    public function delete(int $id): bool;
}
