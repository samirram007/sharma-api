<?php

namespace Modules\VoucherType\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\VoucherCategory\Resources\VoucherCategoryResource;
use Modules\VoucherClassification\Resources\VoucherClassificationResource;

class VoucherTypeResource extends SuccessResource
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
            'icon' => $this->icon,
            'voucherCategoryId' => $this->voucher_category_id,
            'voucherCategory' => new VoucherCategoryResource($this->whenLoaded('voucher_category')),
            'voucherClassificationId' => $this->voucher_classification_id,
            'voucherClassifications' => VoucherClassificationResource::collection($this->whenLoaded('voucher_classifications')),

        ]);

    }
}
