<?php

namespace Modules\AccountNature\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AccountGroup\Resources\AccountGroupCollection;
use Modules\AccountLedger\Resources\AccountLedgerCollection;

class AccountNatureResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'accountingEffect' => $this->accounting_effect,
            'status' => $this->status,
            'accountGroups' => new AccountGroupCollection($this->whenLoaded('account_groups')),
            'accountLedgers' => new AccountLedgerCollection($this->whenLoaded('account_ledgers')),

        ]);

    }
}
