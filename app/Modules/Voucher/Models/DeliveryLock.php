<?php

namespace Modules\Voucher\Models;

use App\Enums\DeliveryLockReason;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit row for a postponed (locked) delivery note.
 *
 * Locking inserts a row with `is_locked = true` and `unlocked_at = null`;
 * unlocking closes that row (`is_locked = false`, `unlocked_at` set) so the
 * lock history — and who locked/released it (Blameable) — is preserved.
 *
 * @property int $id
 * @property int|null $voucher_id
 * @property DeliveryLockReason|null $reason
 * @property string|null $note
 * @property Carbon|null $locked_at
 * @property Carbon|null $unlocked_at
 * @property bool $is_locked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read \Modules\User\Models\User|null $creator
 * @property-read \Modules\User\Models\User|null $updater
 */
class DeliveryLock extends Model
{
    // Records who opened the lock (created_by) and who closed it (updated_by).
    use Blameable;
    use HasFactory;

    protected $table = 'delivery_locks';

    protected $fillable = [
        'voucher_id',
        'reason',
        'note',
        'locked_at',
        'unlocked_at',
        'is_locked',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'voucher_id' => 'integer',
        'reason' => DeliveryLockReason::class,
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_locked' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function getReasonOptions(): array
    {
        return DeliveryLockReason::getValues();
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Active (current) locks — the ones that mark a delivery as postponed.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_locked', true)->whereNull('unlocked_at');
    }

    /** The delivery is postponed while the lock is open. */
    public function isLocked(): bool
    {
        return $this->is_locked === true && $this->unlocked_at === null;
    }

    /** The lock row was closed by an unlock. */
    public function isUnlocked(): bool
    {
        return $this->is_locked === false && $this->unlocked_at !== null;
    }

    public function getLockStatusAttribute(): string
    {
        return $this->isLocked() ? 'locked' : 'unlocked';
    }
}
