<?php

namespace Modules\Transporter\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AccountLedger\Resources\AccountLedgerResource;
use Modules\Address\Resources\AddressResource;

class TransporterResource extends SuccessResource
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
            'licenseNo' => $this->license_no,
            'vehicleType' => $this->vehicle_type,
            'contactPerson' => $this->contact_person,
            'contactNo' => $this->contact_no,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'accountLedger' => AccountLedgerResource::make($this->whenLoaded('account_ledger')),
            'address' => $this->whenLoaded('address', fn () => $this->address ? AddressResource::make($this->address) : null),

        ]);

    }
}
