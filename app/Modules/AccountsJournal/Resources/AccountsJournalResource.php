<?php

namespace Modules\AccountsJournal\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Modules\AccountLedger\Resources\AccountLedgerResource;

class AccountsJournalResource extends SuccessResource
{
    use CamelCaseResource;

    public function toArray(Request $request): array
    {

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'voucherId' => $this->voucher_id,
            'entryOrder' => $this->entry_order,
            'accountLedgerId' => $this->account_ledger_id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'remarks' => $this->description,
            'accountLedger' => AccountLedgerResource::make($this->whenLoaded('account_ledger')),

        ]);

    }
}
