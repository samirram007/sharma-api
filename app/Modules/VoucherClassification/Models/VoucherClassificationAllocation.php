<?php

namespace Modules\VoucherClassification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccountLedger\Models\AccountLedger;

class VoucherClassificationAllocation extends Model
{
    protected $table = 'voucher_classification_allocations';

    protected $fillable = [
        'voucher_classification_id',
        'ledger_id',
        'allocation_type',
        'value',
        'is_hidden',
        'is_readonly',
        'is_mandatory',
        'rounding_method',
        'rounding_limit',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'rounding_limit' => 'decimal:4',
        'is_hidden' => 'boolean',
        'is_readonly' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VoucherClassification::class, 'voucher_classification_id');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(AccountLedger::class, 'ledger_id');
    }
}
