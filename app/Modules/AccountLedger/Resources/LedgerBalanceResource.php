<?php

namespace Modules\AccountLedger\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class LedgerBalanceResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {
        // The service payload is already a flat camelCase array
        // (['id', 'balance', 'nature']) — convert it as-is.
        return $this->toCamelCaseArray($request);
    }
}
