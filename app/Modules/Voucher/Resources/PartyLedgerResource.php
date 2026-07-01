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

class PartyLedgerResource extends SuccessResource
{
    public function toArray(Request $request): array
    {

        $data = [
            'id' => $this['id'],
            'name' => $this['name'],
            'code' => $this['code'],
            'ledgerableId' => $this['ledgerable_id'],
            'ledgerableType' => $this['ledgerable_type'],
            'currentBalance' => $this['current_balance'],
        ];

        return $data;
    }
}
