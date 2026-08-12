<?php

namespace Modules\PhysicalStockCount\Resources;

use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhysicalStockCountItemResource extends JsonResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'id' => $this->id,
            'physicalStockCountId' => $this->physical_stock_count_id,
            'stockItemId' => $this->stock_item_id,
            'stockItem' => $this->whenLoaded('stock_item', fn () => [
                'id' => $this->stock_item->id,
                'name' => $this->stock_item->name,
                'code' => $this->stock_item->code,
                'stockUnit' => $this->when($this->stock_item->relationLoaded('stock_unit'), fn () => [
                    'id' => $this->stock_item->stock_unit->id,
                    'name' => $this->stock_item->stock_unit->name,
                ]),
            ]),
            'batchNo' => $this->batch_no,
            'serialNo' => $this->serial_no,
            'mfgDate' => $this->mfg_date,
            'expiryDate' => $this->expiry_date,
            'systemQuantity' => (float) $this->system_quantity,
            'physicalQuantity' => (float) $this->physical_quantity,
            'difference' => (float) $this->difference,
            'rate' => (float) $this->rate,
            'amount' => (float) $this->amount,
            'remarks' => $this->remarks,
            'entryOrder' => $this->entry_order,
        ]);
    }
}
