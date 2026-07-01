<?php

namespace Modules\VoucherClassification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClassificationInventoryMap extends Model
{
    protected $table = 'voucher_classification_inventory_maps';

    protected $fillable = [
        'voucher_classification_id',
        'item_group_id',
        'stock_item_id',
        'income_ledger_id',
        'expense_ledger_id',
        'warehouse_id',
    ];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VoucherClassification::class, 'voucher_classification_id');
    }
}
