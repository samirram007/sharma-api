<?php

namespace Modules\DeliveryVehicle\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\Transporter\Resources\TransporterResource;

class DeliveryVehicleResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'transporterId' => $this->transporter_id,
            'vehicleNumber' => $this->vehicle_number,
            'vehicleType' => $this->vehicle_type,
            'capacity' => $this->capacity,
            'driverName' => $this->driver_name,
            'driverContact' => $this->driver_contact,
            'status' => $this->status,
            'transporter' => new TransporterResource($this->whenLoaded('transporter')),

        ]);

    }
}
