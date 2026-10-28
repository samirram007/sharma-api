<?php

namespace Modules\Voucher\Contracts;

use App\Support\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Voucher\Models\DeliveryLock;

interface DeliveryLockRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find current active lock for a voucher.
     */
    public function findCurrentLock(int $voucherId): ?DeliveryLock;

    /**
     * Find all locks for a voucher.
     */
    public function findByVoucher(int $voucherId): Collection;

    /**
     * Create a new delivery lock record.
     */
    public function createLock(array $data): DeliveryLock;

    /**
     * Delete the current active lock for a voucher.
     */
    public function deleteCurrentLock(int $voucherId): bool;

    /**
     * Check if voucher has active lock.
     */
    public function hasActiveLock(int $voucherId): bool;
}
