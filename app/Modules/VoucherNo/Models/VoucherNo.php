<?php

namespace Modules\VoucherNo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;
use Modules\Company\Models\Company;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\VoucherType\Models\VoucherType;

class VoucherNo extends Model
{
    use HasFactory;

    protected $table = 'voucher_nos';

    protected $fillable = [
        'prefix',
        'voucher_type_id',
        'company_id',
        'branch_id',
        'fiscal_year_id',
        'starting_no',
        'current_no',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        // Add any attributes you want to hide from JSON representations
    ];

    protected $appends = [
        // Add any accessors you want to append to JSON representations
    ];

    public function voucher_type(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function fiscal_year(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function getVoucherNoAttribute()
    {
        return $this->prefix.str_pad($this->current_no + 1, 6, '0', STR_PAD_LEFT);
    }
}
