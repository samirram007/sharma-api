<?php

namespace Modules\ReceiptVoucher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptVoucher extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope('receipt_voucher', function ($q) {
            $q->where('voucher_type_id', 1003);
        });
    }
}
