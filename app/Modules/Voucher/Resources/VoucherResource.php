<?php

namespace Modules\Voucher\Resources;

use App\Http\Middleware\RequireFeaturePermission;
use App\Http\Resources\SuccessResource;
use App\Support\DeliveryLockFeatures;
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
        // Exclude computed lock appends and nested lock relations from the
        // generic conversion so the permission-gated fields are only included
        // explicitly below. List rows also skip the heavy entries relation.
        $excluded = [
            'laravel_through_key',
            'delivery_lock_status',
            'delivery_locked',
            'delivery_lock',
            'latest_lock',
        ];

        if ($this->resource->isListMode ?? false) {
            $excluded[] = 'voucher_entries';
        }

        return $excluded;
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

        $payload = array_merge($this->toCamelCaseArray($request), [

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

        // Delivery lock state is derived from the active `delivery_locks` row
        // (see the accessors on the Voucher model). It is only sent to users
        // holding DELIVERY_LOCK_VIEW — the reason/note is delivery information
        // some roles are not meant to read at all, not just see hidden in UI.
        if ($this->canViewDeliveryLock($request)) {
            $payload = array_merge($payload, [
                'deliveryLocked' => $this->delivery_locked,
                'deliveryLockReason' => $this->delivery_lock_reason,
                'deliveryLockNote' => $this->delivery_lock_note,
                // Wall-clock values exactly as stored in `delivery_locks` (no UTC
                // shift) so the grid shows the same time the database holds.
                'deliveryLockedAt' => $this->delivery_locked_at?->format('Y-m-d\TH:i:s'),
                'deliveryLockedBy' => $this->delivery_locked_by,
                'deliveryLockedByName' => $this->delivery_locked_by_name,
                'deliveryUnlockedAt' => $this->delivery_unlocked_at?->format('Y-m-d\TH:i:s'),
                'deliveryUnlockedBy' => $this->delivery_unlocked_by,
                'deliveryUnlockedByName' => $this->delivery_unlocked_by_name,
                'deliveryLockStatus' => $this->delivery_lock_status,
                'deliveryLock' => DeliveryLockResource::make($this->whenLoaded('delivery_lock')),
                'latestDeliveryLock' => DeliveryLockResource::make($this->whenLoaded('latest_lock')),
            ]);
        }

        return $payload;
    }

    /**
     * DELIVERY_LOCK_VIEW, resolved once per request — a list page serializes
     * hundreds of vouchers, so the role/permission lookup must not repeat.
     */
    private function canViewDeliveryLock(Request $request): bool
    {
        $cached = $request->attributes->get('delivery_lock_view');

        if ($cached === null) {
            $cached = RequireFeaturePermission::allows(
                $request->user(),
                DeliveryLockFeatures::VIEW,
            );

            $request->attributes->set('delivery_lock_view', $cached);
        }

        return (bool) $cached;
    }
}
