<?php

namespace Modules\Transporter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\Address\Models\Address;

class Transporter extends Model
{
    use HasFactory;

    protected $table = 'transporters';

    protected $fillable = [
        'name',
        'code',
        'gstin',
        'pan',
        'license_no',
        'vehicle_type',
        'contact_person',
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
}
