<?php

namespace Modules\ReceiptVoucher\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\ReceiptVoucher\Contracts\ReceiptVoucherServiceInterface;

class ReceiptVoucherFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ReceiptVoucherServiceInterface::class;
    }
}
