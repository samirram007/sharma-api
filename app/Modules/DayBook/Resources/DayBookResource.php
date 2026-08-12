<?php

namespace Modules\DayBook\Resources;

use Illuminate\Http\Request;
use Modules\Voucher\Resources\VoucherResource;

class DayBookResource extends VoucherResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
