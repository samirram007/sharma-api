<?php

namespace Modules\PhysicalStockCount\Resources;

use App\Http\Resources\SuccessResource;
use Illuminate\Http\Request;
use Modules\PhysicalStockCount\Models\PhysicalStockCount;

class PhysicalStockCountResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof PhysicalStockCount) {
            return [
                'id' => $this->id,
                'fiscalYearId' => $this->fiscal_year_id,
                'fiscalYear' => $this->whenLoaded('fiscal_year', fn () => [
                    'id' => $this->fiscal_year->id,
                    'name' => $this->fiscal_year->name,
                ]),
                'godownId' => $this->godown_id,
                'godown' => $this->whenLoaded('godown', fn () => [
                    'id' => $this->godown->id,
                    'name' => $this->godown->name,
                    'code' => $this->godown->code,
                ]),
                'countDate' => $this->count_date,
                'status' => $this->status,
                'countedBy' => $this->counted_by,
                'countedByUser' => $this->whenLoaded('counted_by_user', fn () => [
                    'id' => $this->counted_by_user->id,
                    'name' => $this->counted_by_user->name,
                ]),
                'items' => PhysicalStockCountItemResource::collection($this->whenLoaded('items')),
                'totalItems' => $this->whenCounted('items'),
                'totalDifference' => $this->when(
                    $this->relationLoaded('items'),
                    fn () => $this->items->sum('difference')
                ),
                'remarks' => $this->remarks,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ];
        }

        return parent::toArray($request);
    }
}
