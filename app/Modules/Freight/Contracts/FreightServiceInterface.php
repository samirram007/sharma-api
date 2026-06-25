<?php

namespace App\Modules\Freight\Contracts;

use App\Modules\Voucher\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Modules\Freight\Models\Freight;

interface FreightServiceInterface
{
    public function getAll(): Collection;
    public function getDeliveryNote(int $page=1,int $perPage = 10, array $filters = []): LengthAwarePaginator;
    public function getDeliveryNoteOverallTotalFare(array $filters = []): float;
    public function godownWiseReport(): Collection;
    public function zoneWiseReport(): Collection;
    public function deliveryNoteZoneWiseReport(): Collection;
    public function deliveryNoteGodownWiseReport(?int $zoneId = null, ?int $godownId = null): Collection;
    public function transporterWiseReport(): Collection;
    public function vehicleWiseReport(): Collection;
    public function voucherWiseReport(): Collection;
    public function billingPreferenceReport(): Collection;
    public function getById(int $id): ?Freight;
    public function store(array $data): Voucher;
    public function update(array $data, int $id): Freight;
    public function delete(int $id): bool;
}
