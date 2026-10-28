<?php

namespace Modules\Voucher\Contracts;

use Modules\Voucher\Models\DeliveryLock;
use Modules\Voucher\Models\Voucher;

interface DeliveryLockServiceInterface
{
    /**
     * Lock or unlock a delivery note/voucher.
     *
     * Locking creates (or refreshes) the active lock row with a preset reason
     * and optional note; unlocking closes the active row (unlocked_at +
     * is_locked = false) so the lock history is preserved.
     */
    public function lockOrUnlock(int $id, bool $lock, array $payload): DeliveryLock;

    /**
     * Get current active lock for a voucher
     */
    public function getCurrentLock(int $voucherId): ?DeliveryLock;

    /**
     * Check if a voucher has an active lock
     */
    public function hasActiveLock(Voucher $voucher): bool;

    /**
     * Get lock reason options
     */
    public function getReasonOptions(): array;
}
