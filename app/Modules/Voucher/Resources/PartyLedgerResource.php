<?php

namespace Modules\Voucher\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class PartyLedgerResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return $this->toCamelCaseArray($request);
    }
}
