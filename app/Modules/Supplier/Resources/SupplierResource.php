<?php

namespace Modules\Supplier\Resources;

use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountLedger\Resources\AccountLedgerResource;
use Modules\Address\Resources\AddressResource;
use Modules\GstRegistrationType\Resources\GstRegistrationTypeResource;
use Illuminate\Http\Request;

use App\Http\Resources\SuccessResource;
class SupplierResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
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
            'address' => $this->whenLoaded('address', fn() => $this->address ? AddressResource::make($this->address) : null),
            'gstRegistrationTypeId' => $this->gst_registration_type_id,
            'gstRegistrationType' => GstRegistrationTypeResource::make($this->whenLoaded('gst_registration_type')),

        ];


    }
}
