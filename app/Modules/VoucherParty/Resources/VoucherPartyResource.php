<?php

namespace Modules\VoucherParty\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Country\Resources\CountryResource;
use Modules\State\Resources\StateResource;
use Modules\Voucher\Resources\VoucherResource;

class VoucherPartyResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'mailingName' => $this->mailing_name,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'line3' => $this->line3,
            'stateId' => $this->state_id,
            'state' => StateResource::make($this->whenLoaded('state')),
            'countryId' => $this->country_id,
            'country' => CountryResource::make($this->whenLoaded('country')),
            'gstRegistrationTypeId' => $this->gst_registration_type_id,
            'gstin' => $this->gstin,
            'placeOfSupplyStateId' => $this->place_of_supply_state_id,
            'voucherId' => $this->voucher_id,
            'voucher' => VoucherResource::make($this->whenLoaded('voucher')),

        ]);

    }
}
