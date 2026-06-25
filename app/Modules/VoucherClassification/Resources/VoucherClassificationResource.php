<?php

namespace App\Modules\VoucherClassification\Resources;

use App\Modules\VoucherType\Resources\VoucherTypeResource;
use Illuminate\Http\Request;

use App\Http\Resources\SuccessResource;
class VoucherClassificationResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'moduleLink' => $this->module_link,
            'status' => $this->status,
            'inclusionRules' => $this->inclusion_rules,
            'exclusionRules' => $this->exclusion_rules,
            'defaultValue' => $this->default_value,
            'percentage' => $this->percentage,
            'voucherTypeId' => $this->voucher_type_id,
            'voucherType' => new VoucherTypeResource($this->whenLoaded('voucher_type')),
        ];
    }
}
