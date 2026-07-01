<?php

namespace Modules\DeliveryRoute\Resources;

use Modules\DeliveryPlace\Resources\DeliveryPlaceResource;
use Modules\Godown\Models\Godown;
use Modules\Godown\Resources\GodownResource;
use Modules\StockUnit\Resources\StockUnitResource;
use Modules\Transporter\Resources\TransporterResource;
use Illuminate\Http\Request;

use App\Http\Resources\SuccessResource;
class DeliveryRouteResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transporterId' => $this->transporter_id,
            'sourcePlaceId' => $this->source_place_id,
            'destinationPlaceId' => $this->destination_place_id,
            'distanceKm' => $this->distance_km,
            'estimatedTimeInMinutes' => $this->estimated_time_in_minutes,
            'rate' => $this->rate,
            'rateUnitId' => $this->rate_unit_id,
            'vehicleNo' => $this->vehicle_no,
            'transporter' => TransporterResource::make($this->whenLoaded('transporter')),
            'rateUnit' => StockUnitResource::make($this->whenLoaded('rate_unit')),
            'sourcePlace' => new GodownResource($this->whenLoaded('source_place')),
            'destinationPlace' => new DeliveryPlaceResource($this->whenLoaded('destination_place')),
        ];
    }
}
