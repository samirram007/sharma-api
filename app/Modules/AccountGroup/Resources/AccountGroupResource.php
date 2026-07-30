<?php

namespace Modules\AccountGroup\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AccountLedger\Resources\AccountLedgerCollection;
use Modules\AccountNature\Resources\AccountNatureResource;

class AccountGroupResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'description' => $this->description,
            'parentId' => $this->parent_id,
            'accountNatureId' => $this->account_nature_id,
            'accountNature' => new AccountNatureResource($this->whenLoaded('account_nature')),
            'accountLedgers' => new AccountLedgerCollection($this->whenLoaded('account_ledgers')),

        ]);

    }
}
