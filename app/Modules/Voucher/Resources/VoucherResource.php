<?php

namespace Modules\Voucher\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Company\Resources\CompanyResource;
use Modules\FiscalYear\Resources\FiscalYearResource;
use Modules\StockJournal\Resources\StockJournalResource;
use Modules\VoucherDispatchDetail\Resources\VoucherDispatchDetailResource;
use Modules\VoucherEntry\Resources\VoucherEntryResource;
use Modules\VoucherParty\Resources\VoucherPartyResource;
use Modules\VoucherReference\Resources\VoucherReferenceResource;
use Modules\VoucherType\Resources\VoucherTypeResource;

class VoucherResource extends SuccessResource
{
    use CamelCaseResource;

    protected function getCamelCaseExcludeFields(): array
    {
        // List rows never render voucherEntries — skip converting them
        // (the bulk of the work in a list payload) and override with [].
        // (Mirrors the CamelCaseResource trait default + the entries relation.)
        return $this->resource->isListMode ?? false
            ? ['laravel_through_key', 'voucher_entries']
            : ['laravel_through_key'];
    }

    public function toArray(Request $request): array
    {
        // List rows never render voucherEntries (edit screens load them via
        // getById) — drop the relation before model serialization so the huge
        // per-voucher entry/ledger arrays are never built in list responses.
        // amount is pre-set as a relation by the service, so the accessor stays
        // correct even with the entries emptied.
        if ($this->resource->isListMode ?? false) {
            $this->resource->setRelation('voucher_entries', new Collection);
        }

        return array_merge($this->toCamelCaseArray($request), [

            'id' => $this->id,
            'voucherNo' => $this->voucher_no,
            'voucherDate' => $this->voucher_date,
            'referenceNo' => $this->reference_no,
            'referenceDate' => $this->reference_date,
            'voucherTypeId' => $this->voucher_type_id,
            'voucherClassId' => $this->voucher_class_id,
            'module' => $this->module,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'fiscalYearId' => $this->fiscal_year_id,
            'companyId' => $this->company_id,
            'stockJournalId' => $this->stock_journal_id,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),

            'partyLedger' => PartyLedgerResource::make($this->whenLoaded('party_ledger')),
            'transactionLedger' => TransactionLedgerResource::make($this->whenLoaded('transaction_ledger')),
            'amount' => $this->amount,
            'paymentStatus' => $this->payment_status,
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'voucherType' => VoucherTypeResource::make($this->whenLoaded('voucher_type')),
            'fiscalYear' => FiscalYearResource::make($this->whenLoaded('fiscal_year')),
            'stockJournal' => StockJournalResource::make($this->whenLoaded('stock_journal')),
            'voucherEntries' => VoucherEntryResource::collection($this->whenLoaded('voucher_entries')),
            'party' => VoucherPartyResource::make($this->whenLoaded('voucher_party')),
            'voucherDispatchDetail' => VoucherDispatchDetailResource::make($this->whenLoaded('voucher_dispatch_detail')),
            'voucherReferences' => VoucherReferenceResource::collection($this->whenLoaded('voucher_references')),
            'referencedBy' => VoucherReferenceResource::collection($this->whenLoaded('referenced_by')),

        ]);

    }
}
