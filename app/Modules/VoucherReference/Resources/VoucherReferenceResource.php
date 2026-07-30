<?php

namespace Modules\VoucherReference\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Voucher\Resources\VoucherResource;

class VoucherReferenceResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'voucherId' => $this->voucher_id,
            'refVoucherId' => $this->ref_voucher_id,
            'voucher' => VoucherResource::make($this->whenLoaded('voucher')),
            'referenceVoucher' => VoucherResource::make($this->whenLoaded('reference_voucher')),
            'type' => $this->type,

        ]);

    }
}
