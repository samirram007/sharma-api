<?php

namespace Modules\Godown\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Support\Collection;
use Modules\Godown\Models\Godown;

interface GodownServiceInterface extends BaseServiceInterface
{
    public function getGodownItemStocks(int $item_id): Collection;

    public function getGodownItemBatches(int $item_id, int $godown_id): Collection;

    public function getZones(): Collection;

    public function getZoneById(int $id): ?Godown;
}
