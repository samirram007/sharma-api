<?php

namespace Modules\VoucherCategory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VoucherType\Models\VoucherType;

class VoucherCategory extends Model
{
    use HasFactory;

    protected $table = 'voucher_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
        'module_link',
        'status',
        'icon',

    ];

    protected $casts = [

    ];

    public function voucher_types(): HasMany
    {
        return $this->hasMany(VoucherType::class);
    }
}
