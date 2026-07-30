<?php

namespace Modules\State\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Country\Resources\CountryResource;

class StateResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'gstCode' => $this->gst_code,
            'countryId' => $this->country_id,
            'country' => CountryResource::make($this->whenLoaded('country')),

        ]);

    }
}
