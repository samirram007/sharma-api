<?php

namespace Modules\OpeningBalance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpeningBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:500'],
            'ledger_entries' => ['nullable', 'array'],
            'ledger_entries.*.ledger_id' => ['required', 'integer', 'exists:account_ledgers,id'],
            'ledger_entries.*.amount' => ['required', 'numeric', 'min:0'],
            'stock_entries' => ['nullable', 'array'],
            'stock_entries.*.item_id' => ['required', 'integer', 'exists:stock_items,id'],
            'stock_entries.*.godowns' => ['nullable', 'array'],
            'stock_entries.*.godowns.*.godown_id' => ['required', 'integer', 'exists:godowns,id'],
            'stock_entries.*.godowns.*.quantity' => ['required', 'numeric', 'min:0'],
            // Optional batch details carried forward from the previous FY closing stock
            'stock_entries.*.godowns.*.batch_no' => ['nullable', 'string', 'max:255'],
            'stock_entries.*.godowns.*.mfg_date' => ['nullable', 'date'],
            'stock_entries.*.godowns.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
