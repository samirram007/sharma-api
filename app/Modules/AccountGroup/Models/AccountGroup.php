<?php

namespace Modules\AccountGroup\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountNature\Models\AccountNature;

class AccountGroup extends Model
{
    use HasFactory;

    protected $table = 'account_groups';

    protected $fillable = [
        'name', 'code', 'account_nature_id', 'description', 'status', 'icon',
    ];

    public function account_nature()
    {
        return $this->belongsTo(AccountNature::class);
    }

    public function account_ledgers()
    {
        return $this->hasMany(AccountLedger::class);
    }
}
