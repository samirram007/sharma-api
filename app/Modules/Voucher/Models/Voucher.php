<?php

namespace Modules\Voucher\Models;

use App\Enums\DeliveryLockReason;
use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Modules\AccountsJournal\Models\AccountsJournal;
use Modules\Company\Models\Company;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\StockJournal\Models\StockJournal;
use Modules\User\Models\User;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;
use Modules\VoucherEntry\Models\VoucherEntry;
use Modules\VoucherParty\Models\VoucherParty;
use Modules\VoucherReference\Models\VoucherReference;
use Modules\VoucherType\Models\VoucherType;

/**
 * @property int $id
 * @property string $voucher_no
 * @property Carbon $voucher_date
 * @property string|null $remarks
 * @property int|null $stock_journal_id
 * @property Carbon $created_at
 */
class Voucher extends Model
{
    use Blameable;
    use HasFactory;

    protected $table = 'vouchers';

    protected $fillable = [
        'voucher_no',
        'voucher_date',
        'reference_no',
        'reference_date',
        'voucher_type_id',
        'remarks',
        'payment_mode',
        'status',

        'fiscal_year_id',
        'company_id',
        'stock_journal_id',
        'module',
        'is_effecting',
        'is_optional',
        'effects_account',
        'effects_stock',
    ];

    /**
     * Set by VoucherService for list responses — tells VoucherResource to skip
     * serializing voucherEntries (edit screens load them via getById instead).
     * Plain property, not persisted, not serialized.
     */
    public bool $isListMode = false;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'voucher_date' => 'date',
        'reference_date' => 'date',
        'is_effecting' => 'boolean',
        'is_optional' => 'boolean',

        'effects_account' => 'boolean',
        'effects_stock' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('default_order', function (Builder $builder) {
            $builder
                ->orderBy('voucher_date', 'desc')
                ->orderBy('voucher_no', 'desc');
        });
    }

    public function stock_journal(): BelongsTo
    {
        return $this->belongsTo(StockJournal::class);
    }

    public function voucher_type(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscal_year(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accounts_journals(): HasMany
    {
        return $this->hasMany(AccountsJournal::class);
    }

    public function voucher_entries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }

    public function voucher_party(): HasOne
    {
        return $this->hasOne(VoucherParty::class, 'voucher_id');
    }

    public function voucher_dispatch_detail(): HasOne
    {
        return $this->hasOne(VoucherDispatchDetail::class, 'voucher_id');
    }

    /**
     * Active (current) delivery lock — the row that marks the delivery note as
     * postponed. Closed rows stay in `deliveryLocks()` as history.
     */
    public function delivery_lock(): HasOne
    {
        return $this->hasOne(DeliveryLock::class, 'voucher_id')
            ->where('is_locked', true)
            ->whereNull('unlocked_at')
            ->latestOfMany();
    }

    /** Every lock/unlock record for this delivery note, newest first. */
    public function deliveryLocks(): HasMany
    {
        return $this->hasMany(DeliveryLock::class, 'voucher_id')->latest('id');
    }

    /**
     * The newest lock row regardless of its state — open while the delivery is
     * postponed, closed once it has been released. Carries the release time
     * and the user who performed the unlock.
     */
    public function latest_lock(): HasOne
    {
        return $this->hasOne(DeliveryLock::class, 'voucher_id')->latestOfMany();
    }

    /** The active lock row, or null when the delivery is not postponed. */
    private function currentDeliveryLock(): ?DeliveryLock
    {
        if ($this->relationLoaded('delivery_lock')) {
            return $this->getRelation('delivery_lock');
        }

        // Cache the lookup on the model so the delivery-lock accessors below
        // don't each fire their own query for the same voucher.
        $lock = $this->delivery_lock()->first();
        $this->setRelation('delivery_lock', $lock);

        return $lock;
    }

    /**
     * Locked when an open lock row exists: locked_at set, is_locked true and
     * unlocked_at null. Unlocking closes the row, so it no longer counts.
     */
    public function isDeliveryLocked(): bool
    {
        $lock = $this->currentDeliveryLock();

        return $lock !== null && $lock->isLocked();
    }

    public function getDeliveryLockStatusAttribute(): string
    {
        return $this->isDeliveryLocked() ? 'locked' : 'unlocked';
    }

    /** Exposed on every voucher payload from the active delivery_locks row. */
    public function getDeliveryLockedAttribute(): bool
    {
        return $this->isDeliveryLocked();
    }

    public function getDeliveryLockReasonAttribute(): ?string
    {
        $reason = $this->currentDeliveryLock()?->reason;

        return $reason instanceof DeliveryLockReason ? $reason->value : $reason;
    }

    public function getDeliveryLockNoteAttribute(): ?string
    {
        return $this->currentDeliveryLock()?->note;
    }

    /** Postponement moment — kept after a release so the list can show both
     *  the lock and the unlock time of the latest lock record. */
    public function getDeliveryLockedAtAttribute(): mixed
    {
        return $this->currentDeliveryLock()?->locked_at
            ?? $this->lastDeliveryLock()?->locked_at;
    }

    /** The newest lock row (open or closed), cached on the model. */
    private function lastDeliveryLock(): ?DeliveryLock
    {
        if ($this->relationLoaded('latest_lock')) {
            return $this->getRelation('latest_lock');
        }

        $lock = $this->latest_lock()->first();
        $this->setRelation('latest_lock', $lock);

        return $lock;
    }

    /** When the delivery was released — null while it is still postponed. */
    public function getDeliveryUnlockedAtAttribute(): mixed
    {
        $lock = $this->lastDeliveryLock();

        return $lock?->isUnlocked() ? $lock->unlocked_at : null;
    }

    /** Blameable: user who opened the current lock. */
    public function getDeliveryLockedByAttribute(): ?int
    {
        return $this->currentDeliveryLock()?->created_by;
    }

    public function getDeliveryLockedByNameAttribute(): ?string
    {
        return $this->currentDeliveryLock()?->creator?->name
            ?? $this->lastDeliveryLock()?->creator?->name;
    }

    /** Blameable: user who released the latest (closed) lock. */
    public function getDeliveryUnlockedByAttribute(): ?int
    {
        $lock = $this->lastDeliveryLock();

        return $lock?->isUnlocked() ? $lock->updated_by : null;
    }

    public function getDeliveryUnlockedByNameAttribute(): ?string
    {
        $lock = $this->lastDeliveryLock();

        return $lock?->isUnlocked() ? $lock->updater?->name : null;
    }

    protected $appends = ['party_ledger', 'transaction_ledger', 'amount', 'payment_status', 'delivery_lock_status', 'delivery_locked'];

    public function voucher_references(): HasMany
    {
        return $this->hasMany(VoucherReference::class, 'voucher_id', 'id');
    }

    public function referenced_by(): HasMany
    {
        return $this->hasMany(VoucherReference::class, 'ref_voucher_id', 'id');
    }

    public function getPartyLedgerAttribute()
    {
        if (! isset($this->relations['party_ledger'])) {
            return null;
        }

        return $this->relations['party_ledger'];
    }

    public function getTransactionLedgerAttribute()
    {
        if (! isset($this->relations['transaction_ledger'])) {
            return null;
        }

        return $this->relations['transaction_ledger'];
    }

    public function getAmountAttribute()
    {
        if (! isset($this->relations['amount'])) {
            return $this->voucher_entries->sum(fn ($entry) => $entry->debit ?: $entry->credit ?: 0);
        }

        return $this->relations['amount'];
    }

    public function getPaymentStatusAttribute()
    {
        // Bulk-resolved value set by VoucherService for collection responses —
        // skips the 2-4 fresh queries + lazy loads this accessor would otherwise
        // run for every voucher during list serialization. Stored as a relation
        // (not an attribute) so it can never leak into a save()/isDirty() call.
        if (isset($this->relations['payment_status'])) {
            return $this->relations['payment_status'];
        }

        $paymentReference = $this->referenced_by()->whereIn('type', ['payment', 'freight_payment'])->get();
        $paymentVouchersIds = $paymentReference->pluck('voucher_id')->toArray();
        // \Log::info($paymentVouchersIds);
        $paymentVouchers = Voucher::whereIn('id', $paymentVouchersIds)->get();

        $totalPaidAmount = $paymentVouchers->sum(fn ($voucher) => $voucher->amount);
        // \Log::info([$this->id, $totalPaidAmount]);
        // return $totalPaidAmount;
        if ($totalPaidAmount >= $this->amount) {
            return 'paid';
        } elseif ($totalPaidAmount > 0) {
            return 'partially_paid';
        } else {
            return 'unpaid';
        }
    }
}
