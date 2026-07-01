<?php

namespace Modules\Voucher\Resources;

use App\Http\Resources\SuccessResource;
use Modules\Company\Resources\CompanyResource;
use Modules\FiscalYear\Resources\FiscalYearResource;
use Modules\StockJournal\Resources\StockJournalResource;
use Modules\VoucherEntry\Resources\VoucherEntryResource;
use Modules\VoucherType\Resources\VoucherTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionLedgerResource extends SuccessResource
{
    public function toArray(Request $request): array
    {
        //dd($this);
        $data = [
            'id' => $this['id'],
            'name' => $this['name'],
            'code' => $this['code'],
            'accountGroupId' => $this['account_group_id'],
            'currentBalance' => $this['current_balance'],
        ];

        return $data;
    }
}
// function array_keys_to_camel_case(array $array): array
// {
//     return collect($array)->mapWithKeys(fn($value, $key) => [Str::camel($key) => $value])->all();
// }
