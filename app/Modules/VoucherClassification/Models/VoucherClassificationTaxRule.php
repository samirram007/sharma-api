<?php

namespace Modules\VoucherClassification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClassificationTaxRule extends Model
{
    protected $table = 'voucher_classification_tax_rules';

    protected $fillable = [
        'voucher_classification_id',
        'tax_ledger_id',
        'calculation_basis',
        'percentage',
        'is_override_allowed',
        'sort_order',
    ];

    protected $casts = [
        'percentage' => 'decimal:4',
        'is_override_allowed' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VoucherClassification::class, 'voucher_classification_id');
    }

    public function taxLedger(): BelongsTo
    {
        return $this->belongsTo(\Modules\AccountLedger\Models\AccountLedger::class, 'tax_ledger_id');
    }
}
