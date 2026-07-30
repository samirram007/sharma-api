<?php

namespace Modules\VoucherClassification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Models\Branch;
use Modules\Company\Models\Company;
use Modules\VoucherType\Models\VoucherType;

class VoucherClassification extends Model
{
    use HasFactory;

    protected $table = 'voucher_classifications';

    protected $fillable = [
        'company_id',
        'branch_id',
        'voucher_type_id',
        'name',
        'code',
        'description',
        'status',
        'is_default',
        'is_system_defined',
        'requires_approval',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_system_defined' => 'boolean',
        'requires_approval' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function voucher_type(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function filters(): HasMany
    {
        return $this->hasMany(VoucherClassificationFilter::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VoucherClassificationAllocation::class);
    }

    public function tax_rules(): HasMany
    {
        return $this->hasMany(VoucherClassificationTaxRule::class);
    }

    public function inventory_maps(): HasMany
    {
        return $this->hasMany(VoucherClassificationInventoryMap::class);
    }

    public function ui_configs(): HasMany
    {
        return $this->hasMany(VoucherClassificationUiConfig::class);
    }
}
