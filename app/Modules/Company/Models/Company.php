<?php

namespace Modules\Company\Models;

use Modules\CompanyType\Models\CompanyType;
use Modules\Country\Models\Country;
use Modules\Currency\Models\Currency;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Address\Models\Address;
use Modules\State\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;


class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'code',
        'company_type_id',
        'phone_no',
        'mobile_no',
        'email',
        'mailing_name',
        'website',
        'cin_no',
        'tin_no',
        'tan_no',
        'gst_no',
        'pan_no',
        'logo',
        'currency_id',
        'status',
        'is_group_company',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_group_company' => 'boolean',
    ];

    public function company_type(): BelongsTo
    {
        return $this->belongsTo(CompanyType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function fiscal_years(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
