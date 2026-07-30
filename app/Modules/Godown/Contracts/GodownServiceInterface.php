<?php

namespace Modules\Godown\Contracts;

use Illuminate\Support\Collection;
use Modules\Godown\Models\Godown;

interface GodownServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): ?Godown;

    public function store(array $data): Godown;

    public function update(array $data, int $id): Godown;

    public function delete(int $id): bool;

    public function getGodownItemStocks(int $item_id): Collection;

    public function getGodownItemBatches(int $item_id, int $godown_id): Collection;

    public function getZones(): Collection;

    public function getZoneById(int $id): ?Godown;
}
