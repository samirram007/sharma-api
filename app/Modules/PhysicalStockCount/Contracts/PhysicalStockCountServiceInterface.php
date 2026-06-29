<?php

namespace App\Modules\PhysicalStockCount\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Modules\PhysicalStockCount\Models\PhysicalStockCount;

interface PhysicalStockCountServiceInterface
{
    public function getAll(): Collection;
    public function getById(int $id): ?PhysicalStockCount;
    public function store(array $data): PhysicalStockCount;
    public function update(array $data, int $id): PhysicalStockCount;
    public function delete(int $id): bool;

    /**
     * Auto-populate system quantities for a count sheet based on stock movements in the FY
     */
    public function populateSystemQuantities(int $countId): PhysicalStockCount;

    /**
     * Mark count as verified (locks quantities)
     */
    public function verify(int $countId): PhysicalStockCount;

    /**
     * Generate adjustment voucher from verified count differences
     */
    public function generateAdjustment(int $countId): PhysicalStockCount;
}
