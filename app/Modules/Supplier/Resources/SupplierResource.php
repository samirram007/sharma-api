<?php

namespace Modules\Supplier\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AccountLedger\Resources\AccountLedgerResource;
use Modules\Address\Resources\AddressResource;
use Modules\GstRegistrationType\Resources\GstRegistrationTypeResource;

class SupplierResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'contactPerson' => $this->contact_person,
            'contactNo' => $this->contact_no,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'accountLedger' => AccountLedgerResource::make($this->whenLoaded('account_ledger')),
            'address' => $this->whenLoaded('address', fn () => $this->address ? AddressResource::make($this->address) : null),
            'gstRegistrationTypeId' => $this->gst_registration_type_id,
            'gstRegistrationType' => GstRegistrationTypeResource::make($this->whenLoaded('gst_registration_type')),

        ]);

    }
}
