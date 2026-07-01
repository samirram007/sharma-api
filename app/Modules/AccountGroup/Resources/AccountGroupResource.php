<?php

namespace Modules\AccountGroup\Resources;

use App\Http\Resources\SuccessResource;
use Modules\AccountLedger\Resources\AccountLedgerCollection;
use Modules\AccountNature\Resources\AccountNatureResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class AccountGroupResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'description' => $this->description,
            'parentId' => $this->parent_id,
            'accountNatureId' => $this->account_nature_id,
            'accountNature' => new AccountNatureResource($this->whenLoaded('account_nature')),
            'accountLedgers' => new AccountLedgerCollection($this->whenLoaded('account_ledgers')),

        ];
    }
}
