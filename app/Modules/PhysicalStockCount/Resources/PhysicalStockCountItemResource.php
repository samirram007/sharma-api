<?php

namespace Modules\PhysicalStockCount\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhysicalStockCountItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'physical_stock_count_id' => $this->physical_stock_count_id,
            'stock_item_id' => $this->stock_item_id,
            'stock_item' => $this->whenLoaded('stock_item', fn () => [
                'id' => $this->stock_item->id,
                'name' => $this->stock_item->name,
                'code' => $this->stock_item->code,
                'stock_unit' => $this->when($this->stock_item->relationLoaded('stock_unit'), fn () => [
                    'id' => $this->stock_item->stock_unit->id,
                    'name' => $this->stock_item->stock_unit->name,
                ]),
            ]),
            'batch_no' => $this->batch_no,
            'serial_no' => $this->serial_no,
            'mfg_date' => $this->mfg_date,
            'expiry_date' => $this->expiry_date,
            'system_quantity' => (float) $this->system_quantity,
            'physical_quantity' => (float) $this->physical_quantity,
            'difference' => (float) $this->difference,
            'rate' => (float) $this->rate,
            'amount' => (float) $this->amount,
            'remarks' => $this->remarks,
            'entry_order' => $this->entry_order,
        ];
    }
}
