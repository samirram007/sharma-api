<?php

namespace Modules\VoucherParty\Models;

use Modules\Country\Models\Country;
use Modules\GstRegistrationType\Models\GstRegistrationType;
use Modules\State\Models\State;
use Modules\Voucher\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VoucherParty extends Model
{
    use HasFactory;

    protected $table = 'voucher_parties';

    protected $fillable = [
        'voucher_id',
        'name',
        'mailing_name',
        'line1',
        'line2',
        'line3',
        'address',
        'state_id',
        'country_id',
        'gst_registration_type_id',
        'gstin',
        'place_of_supply_state_id',


    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function place_of_supply_state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'place_of_supply_state_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function gst_registration_type(): BelongsTo
    {
        return $this->belongsTo(GstRegistrationType::class, 'gst_registration_type_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }
}
