<?php

namespace App\Modules\VoucherClassification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClassificationFilter extends Model
{
    protected $table = 'voucher_classification_filters';

    protected $fillable = [
        'voucher_classification_id',
        'filterable_type',
        'filterable_id',
        'filter_type',
    ];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VoucherClassification::class, 'voucher_classification_id');
    }
}
