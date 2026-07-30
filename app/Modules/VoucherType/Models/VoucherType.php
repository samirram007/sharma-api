<?php

namespace Modules\VoucherType\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VoucherCategory\Models\VoucherCategory;
use Modules\VoucherClassification\Models\VoucherClassification;

class VoucherType extends Model
{
    use HasFactory;

    protected $table = 'voucher_types';

    protected $fillable = [
        'name',
        'parent_id',
        'code',
        'print_name',
        'description',
        'voucher_category_id',
        'voucher_classification_id',
        'is_financial',
        'is_effecting',
        'is_hidden',
        'is_system',
        'status',
        'icon',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->BelongsTo(VoucherType::class);
    }

    public function voucher_category(): BelongsTo
    {
        return $this->BelongsTo(VoucherCategory::class);
    }

    public function voucher_classifications()
    {
        return $this->hasMany(VoucherClassification::class);
    }
}
