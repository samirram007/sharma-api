<?php

namespace Modules\VoucherCategory\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\VoucherType\Resources\VoucherTypeResource;

class VoucherCategoryResource extends SuccessResource
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
            'voucherTypes' => VoucherTypeResource::collection($this->whenLoaded('voucher_types')),

        ]);

    }
}
