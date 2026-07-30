<?php

namespace Modules\VoucherClassification\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\VoucherType\Resources\VoucherTypeResource;

class VoucherClassificationResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

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

        ]);

    }
}
