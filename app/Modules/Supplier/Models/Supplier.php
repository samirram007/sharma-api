<?php

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\Address\Models\Address;
use Modules\GstRegistrationType\Models\GstRegistrationType;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'code',
        'gst_registration_type_id',
        'gstin',
        'pan',
        'contact_person',
        'contact_no',
        'phone',
        'email',
        'status',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function account_ledger(): MorphOne
    {
        return $this->morphOne(AccountLedger::class, 'ledgerable');
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function gst_registration_type(): BelongsTo
    {
        return $this->belongsTo(GstRegistrationType::class, 'gst_registration_type_id');
    }
}
