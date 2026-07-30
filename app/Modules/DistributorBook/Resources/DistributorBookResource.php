<?php

namespace Modules\DistributorBook\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;

class DistributorBookResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'balance' => $this->net_balance,

        ]);

    }
}
