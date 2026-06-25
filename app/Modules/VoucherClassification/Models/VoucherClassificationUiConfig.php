<?php

namespace App\Modules\VoucherClassification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClassificationUiConfig extends Model
{
    protected $table = 'voucher_classification_ui_configs';

    protected $fillable = [
        'voucher_classification_id',
        'field_name',
        'is_visible',
        'is_mandatory',
        'is_readonly',
        'default_value_formula',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_mandatory' => 'boolean',
        'is_readonly' => 'boolean',
    ];

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VoucherClassification::class, 'voucher_classification_id');
    }
}
