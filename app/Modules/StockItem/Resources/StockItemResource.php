<?php

namespace Modules\StockItem\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\StockUnit\Resources\StockUnitResource;

class StockItemResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            // Nested relations — override auto-converted raw arrays with proper Resource classes
            'stockUnit' => StockUnitResource::make($this->whenLoaded('stock_unit')),
            'alternateStockUnit' => StockUnitResource::make($this->whenLoaded('alternate_stock_unit')),
            // Computed attribute — not in model $appends, so not in toArray()
            'stockInHand' => $this->stock_in_hand,
        ]);
    }
}
