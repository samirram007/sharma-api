<?php

namespace Modules\Freight\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Freight\Models\Freight;
use Modules\Voucher\Models\Voucher;

interface FreightServiceInterface extends BaseServiceInterface
{
    public function getAll(): Collection|LengthAwarePaginator;

    public function getDeliveryNote(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator;

    public function getDeliveryNoteOverallTotalFare(array $filters = []): float;

    public function godownWiseReport(): Collection;

    public function zoneWiseReport(): Collection;

    public function deliveryNoteZoneWiseReport(): Collection;

    public function deliveryNoteGodownWiseReport(?int $zoneId = null, ?int $godownId = null): Collection;

    public function transporterWiseReport(): Collection;

    public function transporterItemWiseReport(): Collection;

    public function vehicleWiseReport(): Collection;

    public function voucherWiseReport(): Collection;

    public function billingPreferenceReport(): Collection;

    public function getById(int $id): ?Freight;

    public function store(array $data): Voucher;

    public function update(array $data, int $id): Freight;

    public function delete(int $id): bool;
}
