<?php

namespace Modules\Country\Models;

use Modules\State\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'phone_code',
        'iso_code',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
