<?php

namespace Modules\StockJournalGodownEntryPurge\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class StockJournalGodownEntryPurgeResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return $this->toCamelCaseArray($request);
    }
}
