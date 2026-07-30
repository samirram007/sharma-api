<?php

namespace Modules\AccountNature\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\AccountGroup\Models\AccountGroup;
use Modules\AccountLedger\Models\AccountLedger;

class AccountNature extends Model
{
    use HasFactory;

    protected $table = 'account_natures';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'icon',
        'accounting_effect',

    ];

    protected $casts = [
        'accounting_effect' => 'string',
    ];

    public function account_groups(): HasMany
    {
        return $this->hasMany(AccountGroup::class);
    }

    public function account_ledgers(): HasManyThrough
    {
        return $this->hasManyThrough(AccountLedger::class, AccountGroup::class);

    }
}
