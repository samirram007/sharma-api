<?php

namespace Modules\StockSummary\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class ClosingStockResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        return $this->toCamelCaseArray($request);
    }
}
