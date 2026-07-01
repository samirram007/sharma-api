<?php

namespace Modules\Godown\Resources;

use Modules\Address\Resources\AddressResource;
use Illuminate\Http\Request;

use App\Http\Resources\SuccessResource;
class GodownResource extends SuccessResource
{
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,

            'description' => $this->description,
            'status' => $this->status,

            'storageUnitType' => $this->storage_unit_type,
            'ourStockWithThirdParty' => $this->our_stock_with_third_party,
            'thirdPartyStockWithUs' => $this->third_party_stock_with_us,
            'parentId' => $this->parent_id,
            'parent' => GodownResource::make($this->whenLoaded('parent')),
            'address' => $this->whenLoaded('address', fn() => $this->address ? AddressResource::make($this->address) : null),

            // 'stockInHand' => $this->stock_in_hand,


        ];
    }
}
