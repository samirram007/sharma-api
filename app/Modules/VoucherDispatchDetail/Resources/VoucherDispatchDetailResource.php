<?php

namespace Modules\VoucherDispatchDetail\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\StockUnit\Resources\StockUnitResource;

class VoucherDispatchDetailResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'voucherId' => $this->voucher_id,
            'orderNumber' => $this->order_number,
            'paymentTerms' => $this->payment_terms,
            'otherReferences' => $this->other_references,
            'termsOfDelivery' => $this->terms_of_delivery,
            'receiptDocNo' => $this->receipt_doc_no,
            'dispatchedThrough' => $this->dispatched_through,
            'source' => $this->source,
            'destination' => $this->destination,
            'destinationSecondary' => $this->destination_secondary,
            'billingPreference' => $this->billing_preference,
            'carrierName' => $this->carrier_name,
            'billOfLadingNo' => $this->bill_of_lading_no,
            'billOfLadingDate' => $this->bill_of_lading_date,
            'motorVehicleNo' => $this->motor_vehicle_no,
            'distance' => $this->distance,

            'distanceUnitId' => $this->distance_unit_id,
            'rate' => $this->rate,
            'rateUnitId' => $this->rate_unit_id,
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'weightUnitId' => $this->weight_unit_id,
            'volume' => $this->volume,
            'volumeUnitId' => $this->volume_unit_id,
            'freightBasis' => $this->freight_basis,
            'loadingCharges' => $this->loading_charges,
            'unloadingCharges' => $this->unloading_charges,
            'packingCharges' => $this->packing_charges,
            'insuranceCharges' => $this->insurance_charges,
            'otherCharges' => $this->other_charges,
            'freightCharges' => $this->freight_charges,
            'totalFare' => $this->total_fare,
            'weightUnit' => new StockUnitResource($this->whenLoaded('weightUnit')),
            'quantityUnit' => new StockUnitResource($this->whenLoaded('quantityUnit')),
            'volumeUnit' => new StockUnitResource($this->whenLoaded('volumeUnit')),

        ]);

    }
}
