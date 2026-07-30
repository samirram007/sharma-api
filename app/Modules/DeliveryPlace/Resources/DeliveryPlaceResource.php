<?php

namespace Modules\DeliveryPlace\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Address\Resources\AddressResource;

class DeliveryPlaceResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'placeType' => $this->place_type,
            'isActive' => $this->is_active,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'address' => $this->whenLoaded('address', fn () => $this->address ? AddressResource::make($this->address) : null),

        ]);

    }
}
