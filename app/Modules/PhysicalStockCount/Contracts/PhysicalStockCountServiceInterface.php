<?php

namespace Modules\PhysicalStockCount\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Modules\PhysicalStockCount\Models\PhysicalStockCount;

interface PhysicalStockCountServiceInterface extends BaseServiceInterface
{
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
