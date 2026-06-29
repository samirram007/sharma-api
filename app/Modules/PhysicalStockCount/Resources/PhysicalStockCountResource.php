<?php

namespace App\Modules\PhysicalStockCount\Resources;

use App\Http\Resources\SuccessResource;
use App\Modules\PhysicalStockCount\Models\PhysicalStockCount;

class PhysicalStockCountResource extends SuccessResource
{
    public function toArray($request): array
    {
        $data = parent::toArray($request);

        if ($this->resource instanceof PhysicalStockCount) {
            $data['data'] = [
                'id' => $this->id,
                'fiscal_year_id' => $this->fiscal_year_id,
                'fiscal_year' => $this->whenLoaded('fiscal_year', fn() => [
                    'id' => $this->fiscal_year->id,
                    'name' => $this->fiscal_year->name,
                ]),
                'godown_id' => $this->godown_id,
                'godown' => $this->whenLoaded('godown', fn() => [
                    'id' => $this->godown->id,
                    'name' => $this->godown->name,
                    'code' => $this->godown->code,
                ]),
                'count_date' => $this->count_date,
                'status' => $this->status,
                'counted_by' => $this->counted_by,
                'counted_by_user' => $this->whenLoaded('counted_by_user', fn() => [
                    'id' => $this->counted_by_user->id,
                    'name' => $this->counted_by_user->name,
                ]),
                'items' => PhysicalStockCountItemResource::collection($this->whenLoaded('items')),
                'total_items' => $this->whenCounted('items'),
                'total_difference' => $this->when($this->relationLoaded('items'), fn() =>
                    $this->items->sum('difference')
                ),
                'remarks' => $this->remarks,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }

        return $data;
    }
}
