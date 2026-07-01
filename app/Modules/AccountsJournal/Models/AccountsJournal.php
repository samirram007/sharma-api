<?php

namespace Modules\AccountsJournal\Models;

use Modules\AccountLedger\Models\AccountLedger;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherEntry\Models\VoucherEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountsJournal extends VoucherEntry
{
    use HasFactory;


}
