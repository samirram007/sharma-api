<?php

namespace Modules\Vehicle\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Transporter\Resources\TransporterResource;

class VehicleResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'transporterId' => $this->transporter_id,
            'vehicleType' => $this->vehicle_type,
            'vehicleNumber' => $this->vehicle_no,
            'description' => $this->description,
            'status' => $this->status,
            'transporter' => new TransporterResource($this->whenLoaded('transporter')),

        ]);

    }
}
