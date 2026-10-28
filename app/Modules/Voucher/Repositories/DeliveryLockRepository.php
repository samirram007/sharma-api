<?php

namespace Modules\Voucher\Repositories;

use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Voucher\Contracts\DeliveryLockRepositoryInterface;
use Modules\Voucher\Models\DeliveryLock;

class DeliveryLockRepository extends BaseRepository implements DeliveryLockRepositoryInterface
{
    protected array $searchableFields = [];

    protected array $filterableFields = [];

    public function __construct(DeliveryLock $model)
    {
        parent::__construct($model, false);
    }

    /**
     * Find the current active lock for a voucher.
     * Locked when: is_locked true and unlocked_at null.
     */
    public function findCurrentLock(int $voucherId): ?DeliveryLock
    {
        return DeliveryLock::query()
            ->where('voucher_id', $voucherId)
            ->active()
            ->first();
    }

    /**
     * Find all locks for a voucher (history, newest first).
     */
    public function findByVoucher(int $voucherId): Collection
    {
        return DeliveryLock::query()
            ->where('voucher_id', $voucherId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Create a new delivery lock record.
     */
    public function createLock(array $data): DeliveryLock
    {
        return $this->model->create($data);
    }

    /**
     * Delete the current active lock for a voucher.
     */
    public function deleteCurrentLock(int $voucherId): bool
    {
        $lock = $this->findCurrentLock($voucherId);

        if (! $lock) {
            return false;
        }

        $lock->delete();

        return true;
    }

    /**
     * Check if voucher has an active lock.
     * Locked when: is_locked true and unlocked_at null.
     */
    public function hasActiveLock(int $voucherId): bool
    {
        return DeliveryLock::query()
            ->where('voucher_id', $voucherId)
            ->active()
            ->exists();
    }
}
