<?php

namespace Modules\Voucher\Services;

use App\Enums\DeliveryLockReason;
use App\Support\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Voucher\Contracts\DeliveryLockServiceInterface;
use Modules\Voucher\Models\DeliveryLock;
use Modules\Voucher\Models\Voucher;

class DeliveryLockService extends BaseService implements DeliveryLockServiceInterface
{
    protected string $modelClass = DeliveryLock::class;

    protected array $defaultResource = [];

    public function __construct() {}

    /**
     * Lock or unlock a delivery note while preserving its lock history.
     */
    public function lockOrUnlock(int $id, bool $lock, array $payload): DeliveryLock
    {
        return DB::transaction(function () use ($id, $lock, $payload): DeliveryLock {
            $voucher = Voucher::query()->lockForUpdate()->findOrFail($id);

            if ((int) $voucher->voucher_type_id !== 2001) {
                abort(422, 'Only delivery notes can be locked or unlocked.');
            }

            $activeLock = DeliveryLock::query()
                ->where('voucher_id', $voucher->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($lock) {
                $attributes = [
                    'reason' => $payload['reason'],
                    'note' => $payload['note'] ?? null,
                    'locked_at' => ! empty($payload['locked_at'])
                        ? Carbon::parse($payload['locked_at'])
                        : now(),
                    'unlocked_at' => null,
                    'is_locked' => true,
                ];

                if ($activeLock) {
                    $activeLock->update($attributes);

                    return $activeLock->refresh();
                }

                return DeliveryLock::create([
                    'voucher_id' => $voucher->id,
                    ...$attributes,
                ]);
            }

            if (! $activeLock) {
                $latestLock = DeliveryLock::query()
                    ->where('voucher_id', $voucher->id)
                    ->latest('id')
                    ->first();

                if ($latestLock?->isUnlocked()) {
                    return $latestLock;
                }

                abort(404, 'No active delivery lock found.');
            }

            $activeLock->update([
                'unlocked_at' => ! empty($payload['unlocked_at'])
                    ? Carbon::parse($payload['unlocked_at'])
                    : now(),
                'is_locked' => false,
            ]);

            return $activeLock->refresh();
        });
    }

    /**
     * Get current active lock for a voucher
     */
    public function getCurrentLock(int $voucherId): ?DeliveryLock
    {
        $voucher = Voucher::findOrFail($voucherId);

        return $voucher->delivery_lock;
    }

    /**
     * Check if a voucher has an active lock
     * Locked when: locked_at set, is_locked true, unlocked_at null
     * Unlocked when: unlocked_at set, is_locked false
     */
    public function hasActiveLock(Voucher $voucher): bool
    {
        if (method_exists($voucher, 'isDeliveryLocked')) {
            return $voucher->isDeliveryLocked();
        }

        return $voucher->deliveryLocks()
            ->where('is_locked', true)
            ->whereNull('unlocked_at')
            ->whereNotNull('locked_at')
            ->exists();
    }

    /**
     * Get lock reason options
     */
    public function getReasonOptions(): array
    {
        return DeliveryLockReason::getValues();
    }
}
