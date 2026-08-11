<?php

namespace Modules\Freight\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\AccountLedger\Contracts\AccountLedgerServiceInterface;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\Freight\Contracts\FreightServiceInterface;
use Modules\Freight\Models\Freight;
use Modules\Godown\Contracts\GodownServiceInterface;
use Modules\Godown\Models\Godown;
use Modules\Voucher\Contracts\VoucherServiceInterface;
use Modules\Voucher\Models\Voucher;
use Modules\Voucher\Requests\VoucherRequest;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Models\VoucherDispatchDetail;
use Modules\VoucherDispatchDetail\Requests\VoucherDispatchDetailRequest;
use Modules\VoucherReference\Contracts\VoucherReferenceServiceInterface;

class FreightService extends BaseService implements FreightServiceInterface
{
    protected string $modelClass = Freight::class;

    protected $deliverNoteVoucherTypeID = 2001; // delivery note

    protected $salesVoucherTypeID = 1006; // sales voucher

    protected $receiptVoucherTypeID = 1003;

    protected $salesAccountLedgerID = 3000001; // sales account ledger id

    public function __construct(
        protected AccountLedgerServiceInterface $accountLedgerService,
        protected VoucherServiceInterface $voucherService,
        protected VoucherReferenceServiceInterface $voucherReferenceService,
        protected VoucherDispatchDetailServiceInterface $voucherDispatchDetailService,
        protected GodownServiceInterface $godownService
    ) {
        $this->defaultResource = [
            'voucher_type',
            'voucher_dispatch_detail.weightUnit',
            'voucher_party',
            'voucher_entries.account_ledger',
            'voucher_references.reference_voucher.voucher_dispatch_detail',
            'company',
            'fiscal_year',
        ];
    }

    public function getAll(): Collection
    {
        $vouchers = $this->voucherService->getByModule('freight');

        return Freight::with($this->defaultResource)->get();
    }

    public function getDeliveryNote(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        // Stock journal hierarchy is eager-loaded so the frontend can auto-fill
        // the freight dispatch-details Weight from the delivery note's entries
        // (same shape as getDeliveryNotesWithStockJournals()).
        $query = Voucher::with(array_merge($this->defaultResource, [
            'stock_journal.stock_journal_entries.stock_item',
            'stock_journal.stock_journal_entries.stock_unit',
            'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
        ]))
            ->where('vouchers.voucher_type_id', $this->deliverNoteVoucherTypeID)
            ->whereNotNull('vouchers.stock_journal_id')
            // Fiscal year period scope (uses user's custom reporting period from user_fiscal_years)
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate]);

        // Apply freight_status filter (pending = fare not entered yet, prepared = fare entered, all = both)
        $this->applyFreightStatusFilter($query, $filters);

        // Date range filter
        if (! empty($filters['date_from'])) {
            $query->where('vouchers.voucher_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('vouchers.voucher_date', '<=', $filters['date_to']);
        }

        // Search filter - searches across multiple fields
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vouchers.voucher_no', 'like', "%{$search}%")
                    ->orWhere('vouchers.remarks', 'like', "%{$search}%")
                    ->orWhereHas('voucher_dispatch_detail', function ($sq) use ($search) {
                        $sq->where('carrier_name', 'like', "%{$search}%")
                            ->orWhere('motor_vehicle_no', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhere('bill_of_lading_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('voucher_party', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $vouchers = $query->select('vouchers.*')
            ->distinct()
            ->orderBy('vouchers.voucher_date', 'desc')
            ->orderBy('vouchers.voucher_no', 'desc')
            ->paginate($perPage);

        // Transform each voucher with ledger info
        $vouchers->getCollection()->transform(fn ($voucher) => $this->voucherService->attachLedgerInfo($voucher));

        return $vouchers;
    }

    public function getDeliveryNoteOverallTotalFare(array $filters = []): float
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        $query = Voucher::where('vouchers.voucher_type_id', $this->deliverNoteVoucherTypeID)
            ->whereNotNull('vouchers.stock_journal_id')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate]);

        // Apply freight_status filter
        $this->applyFreightStatusFilter($query, $filters);

        if (! empty($filters['date_from'])) {
            $query->where('vouchers.voucher_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('vouchers.voucher_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vouchers.voucher_no', 'like', "%{$search}%")
                    ->orWhere('vouchers.remarks', 'like', "%{$search}%")
                    ->orWhereHas('voucher_dispatch_detail', function ($sq) use ($search) {
                        $sq->where('carrier_name', 'like', "%{$search}%")
                            ->orWhere('motor_vehicle_no', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhere('bill_of_lading_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('voucher_party', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return (float) $query
            ->join('voucher_dispatch_details', 'vouchers.id', '=', 'voucher_dispatch_details.voucher_id')
            ->sum('voucher_dispatch_details.total_fare');
    }

    /**
     * Apply the freight_status filter to the delivery note query.
     *
     * The status is derived from the dispatch-detail fare, not from whether a
     * freight bill exists:
     * - 'prepared': Dispatch details carry a computed fare — both the base
     *   freight charges and the total fare are filled in (> 0).
     * - 'pending' (default): No fare computed yet — no dispatch detail row,
     *   or either fare field is missing/zero.
     * - 'all' or any other value: No filtering on freight status.
     */
    private function applyFreightStatusFilter($query, array $filters): void
    {
        $status = $filters['freight_status'] ?? 'pending';

        if ($status === 'prepared') {
            // Prepared = both fare fields are filled in on the dispatch detail.
            $query->whereHas('voucher_dispatch_detail', function ($q) {
                $q->where('total_fare', '>', 0)
                    ->where('freight_charges', '>', 0);
            });
        } elseif ($status === 'pending') {
            // Pending = no computed fare yet (no dispatch detail row, or
            // either fare field missing/zero).
            $query->where(function ($q) {
                $q->whereDoesntHave('voucher_dispatch_detail')
                    ->orWhereHas('voucher_dispatch_detail', function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->where('total_fare', '<=', 0)
                                ->orWhereNull('total_fare');
                        })->orWhere(function ($q3) {
                            $q3->where('freight_charges', '<=', 0)
                                ->orWhereNull('freight_charges');
                        });
                    });
            });
        }
        // 'all' — no additional filtering
    }

    public function godownWiseReport(): Collection
    {
        $deliveryNotes = $this->getDeliveryNotesWithStockJournals();
        $godownData = [];

        $this->processStockJournalEntries($deliveryNotes, function ($voucher, $godownEntry, $godown, $movementType, $quantity) use (&$godownData) {
            $key = (string) $godown->id;

            if (! isset($godownData[$key])) {
                $godownData[$key] = $this->initGroupBucket([
                    'godownId' => $godown->id,
                    'godownName' => $godown->name,
                    'godownCode' => $godown->code,
                ]);
            }

            $this->accumulateEntry($godownData[$key], $voucher, $godownEntry, $godown, $movementType, $quantity, 'voucherDetails');
        });

        return new Collection($this->finalizeGroupedData($godownData));
    }

    public function zoneWiseReport(): Collection
    {
        // Get all zones and index by ID for fast lookup
        $zones = Godown::where('storage_unit_type', 'ZONE')->get()->keyBy('id');
        $zoneIds = $zones->keys()->toArray();

        // Get freight (sales) vouchers instead of delivery notes
        $freightVouchers = $this->getFreightVouchersWithReferencedData();
        $zoneData = [];

        foreach ($freightVouchers as $freightVoucher) {
            // Each freight voucher references exactly one delivery note via voucher_references
            $reference = $freightVoucher->voucher_references->first();
            if (! $reference) {
                continue;
            }

            $deliveryNote = $reference->reference_voucher;
            if (! $deliveryNote || ! $deliveryNote->stock_journal) {
                continue;
            }

            $stockJournal = $deliveryNote->stock_journal;

            foreach ($stockJournal->stock_journal_entries as $entry) {
                foreach ($entry->stock_journal_godown_entries as $godownEntry) {
                    $godown = $godownEntry->godown;
                    if (! $godown) {
                        continue;
                    }

                    // Determine zone: godown itself is a zone, or its parent is a zone
                    $zone = null;
                    if (in_array($godown->id, $zoneIds)) {
                        $zone = $zones[$godown->id] ?? null;
                    } elseif ($godown->parent_id && in_array($godown->parent_id, $zoneIds)) {
                        $zone = $zones[$godown->parent_id] ?? null;
                    }

                    $key = $zone ? (string) $zone->id : 'unmapped';

                    if (! isset($zoneData[$key])) {
                        $zoneData[$key] = $this->initGroupBucket([
                            'zoneId' => $zone?->id,
                            'zoneName' => $zone?->name ?? 'Unmapped',
                            'zoneCode' => $zone?->code ?? null,
                        ]);
                    }

                    $movementType = $godownEntry->movement_type?->value ?? '';
                    $quantity = (float) ($godownEntry->actual_quantity ?? 0);

                    $this->accumulateFreightZoneEntry(
                        $zoneData[$key],
                        $freightVoucher,
                        $deliveryNote,
                        $godownEntry,
                        $godown,
                        $movementType,
                        $quantity,
                        'godownDetails'
                    );
                }
            }
        }

        return new Collection($this->finalizeGroupedData($zoneData));
    }

    /**
     * Delivery Note zone-wise report: groups delivery notes by zone using their
     * stock journal godown entries, showing dispatch details.
     */
    public function deliveryNoteZoneWiseReport(): Collection
    {
        $zones = Godown::where('storage_unit_type', 'ZONE')->get()->keyBy('id');
        $zoneIds = $zones->keys()->toArray();

        $deliveryNotes = $this->getDeliveryNotesWithStockJournals();
        $zoneData = [];

        $this->processStockJournalEntries($deliveryNotes, function ($voucher, $godownEntry, $godown, $movementType, $quantity) use (&$zoneData, $zoneIds, $zones) {
            // Determine zone: godown itself is a zone, or its parent is a zone
            $zone = null;
            if (in_array($godown->id, $zoneIds)) {
                $zone = $zones[$godown->id] ?? null;
            } elseif ($godown->parent_id && in_array($godown->parent_id, $zoneIds)) {
                $zone = $zones[$godown->parent_id] ?? null;
            }

            $key = $zone ? (string) $zone->id : 'unmapped';

            if (! isset($zoneData[$key])) {
                $zoneData[$key] = $this->initGroupBucket([
                    'zoneId' => $zone?->id,
                    'zoneName' => $zone?->name ?? 'Unmapped',
                    'zoneCode' => $zone?->code ?? null,
                ]);
            }

            $this->accumulateEntry($zoneData[$key], $voucher, $godownEntry, $godown, $movementType, $quantity, 'godownDetails');
        });

        return new Collection($this->finalizeGroupedData($zoneData));
    }

    /**
     * Delivery Note godown-wise report: groups delivery notes by godown using their
     * stock journal godown entries, showing dispatch details.
     * When a zoneId is provided, only godowns belonging to that zone are included.
     * When a godownId is provided, only that specific godown is included.
     */
    public function deliveryNoteGodownWiseReport(?int $zoneId = null, ?int $godownId = null): Collection
    {
        if (! $zoneId && ! $godownId) {
            return new Collection;
        }

        // Determine which godowns to filter by
        $filteredGodownIds = [];

        if ($godownId) {
            // Specific godown selected
            $filteredGodownIds = [$godownId];
        } elseif ($zoneId) {
            // All godowns belonging to this zone (zone itself + child godowns)
            $filteredGodownIds = Godown::where(function ($query) use ($zoneId) {
                $query->where('id', $zoneId)
                    ->orWhere('parent_id', $zoneId);
            })
                ->pluck('id')
                ->toArray();
        }

        $deliveryNotes = $this->getDeliveryNotesWithStockJournals();
        $godownData = [];

        $this->processStockJournalEntries($deliveryNotes, function ($voucher, $godownEntry, $godown, $movementType, $quantity) use (&$godownData, $filteredGodownIds) {
            // Skip godowns not in the filtered list
            if (! in_array($godown->id, $filteredGodownIds)) {
                return;
            }

            $key = (string) $godown->id;

            if (! isset($godownData[$key])) {
                $godownData[$key] = $this->initGroupBucket([
                    'godownId' => $godown->id,
                    'godownName' => $godown->name,
                    'godownCode' => $godown->code,
                ]);
            }

            $this->accumulateEntry($godownData[$key], $voucher, $godownEntry, $godown, $movementType, $quantity, 'voucherDetails');
        });

        return new Collection($this->finalizeGroupedData($godownData));
    }

    // ---- Shared helpers ----

    /**
     * Get delivery notes with eager-loaded stock journal hierarchy, scoped to current fiscal year.
     */
    private function getDeliveryNotesWithStockJournals(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        return Voucher::with([
            'stock_journal.stock_journal_entries.stock_item',
            'stock_journal.stock_journal_entries.stock_unit',
            'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
            'voucher_dispatch_detail',
            'voucher_party',
        ])
            ->where('vouchers.voucher_type_id', $this->deliverNoteVoucherTypeID)
            ->whereNotNull('vouchers.stock_journal_id')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
            ->select('vouchers.*')
            ->distinct()
            ->get();
    }

    /**
     * Get freight (sales) vouchers with eager-loaded referenced delivery note data
     * (stock journal hierarchy, dispatch details, party) for zone-wise reporting.
     */
    private function getFreightVouchersWithReferencedData(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        return Voucher::with([
            'voucher_references.reference_voucher.stock_journal.stock_journal_entries.stock_item',
            'voucher_references.reference_voucher.stock_journal.stock_journal_entries.stock_unit',
            'voucher_references.reference_voucher.stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
            'voucher_references.reference_voucher.voucher_dispatch_detail',
            'voucher_references.reference_voucher.voucher_party',
            'voucher_party',
        ])
            ->where('vouchers.module', 'freight')
            ->where('vouchers.voucher_type_id', $this->salesVoucherTypeID)
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
            ->select('vouchers.*')
            ->distinct()
            ->orderBy('vouchers.voucher_date', 'desc')
            ->orderBy('vouchers.voucher_no', 'desc')
            ->get();
    }

    /**
     * Get the user's fiscal year period (start + end dates) from their user_fiscal_year record.
     * This is the same pattern used by DayBookService, allowing the ReportingPeriod component
     * to control the date range shown in reports.
     *
     * @return array{0: int, 1: string, 2: string} [fiscal_year_id, start_date, end_date]
     */
    private function getUserFiscalYearPeriod(): array
    {

        $userFiscalYear = auth()->guard()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        return [
            (int) $userFiscalYear->fiscal_year_id,
            $userFiscalYear->start_date,
            $userFiscalYear->end_date,
        ];
    }

    /**
     * Iterate stock journal entries across delivery notes, calling callback for each godown entry.
     * Callback receives: (Voucher $voucher, StockJournalGodownEntry $entry, Godown $godown, string $movementType, float $quantity)
     */
    private function processStockJournalEntries(Collection $deliveryNotes, callable $callback): void
    {
        foreach ($deliveryNotes as $voucher) {
            $stockJournal = $voucher->stock_journal;
            if (! $stockJournal) {
                continue;
            }

            foreach ($stockJournal->stock_journal_entries as $entry) {
                foreach ($entry->stock_journal_godown_entries as $godownEntry) {
                    $godown = $godownEntry->godown;
                    if (! $godown) {
                        continue;
                    }

                    $movementType = $godownEntry->movement_type?->value ?? '';
                    $quantity = (float) ($godownEntry->actual_quantity ?? 0);

                    $callback($voucher, $godownEntry, $godown, $movementType, $quantity);
                }
            }
        }
    }

    /**
     * Initialize a group bucket with default aggregate fields plus identifier fields.
     */
    private function initGroupBucket(array $identifiers): array
    {
        return array_merge($identifiers, [
            'totalEntries' => 0,
            'totalInwardQuantity' => 0,
            'totalOutwardQuantity' => 0,
            'totalClosingQuantity' => 0,
            'totalInwardBillingQuantity' => 0,
            'totalOutwardBillingQuantity' => 0,
            'totalBillingClosingQuantity' => 0,
            'totalAmount' => 0,
            '_voucher_ids' => [],
        ]);
    }

    /**
     * Accumulate a stock journal godown entry into a group bucket.
     */
    private function accumulateEntry(array &$bucket, $voucher, $godownEntry, $godown, string $movementType, float $quantity, string $detailKey): void
    {
        $billingQty = (float) ($godownEntry->billing_quantity ?? 0);
        $stockEntry = $godownEntry->stock_journal_entry;
        $stockUnit = $stockEntry?->stock_unit;

        if ($movementType === 'in') {
            $bucket['totalInwardQuantity'] += $quantity;
            $bucket['totalInwardBillingQuantity'] += $billingQty;
        } elseif ($movementType === 'out') {
            $bucket['totalOutwardQuantity'] += $quantity;
            $bucket['totalOutwardBillingQuantity'] += $billingQty;
        }

        $bucket['totalAmount'] += (float) ($godownEntry->amount ?? 0);
        $bucket['_voucher_ids'][] = $voucher->id;

        $dispatch = $voucher->voucher_dispatch_detail;

        $bucket[$detailKey][] = [
            'voucherId' => $voucher->id,
            'voucherNo' => $voucher->voucher_no,
            'voucherDate' => $voucher->voucher_date?->format('Y-m-d'),
            'partyName' => $voucher->voucher_party?->name,
            'itemId' => $stockEntry?->stock_item_id,
            'itemName' => $stockEntry?->stock_item?->name,
            'unitCode' => $stockUnit?->code,
            'unitName' => $stockUnit?->name,
            'noOfDecimalPlaces' => $stockUnit?->no_of_decimal_places,
            'movementType' => $movementType,
            'actualQuantity' => $quantity,
            'billingQuantity' => $billingQty,
            'amount' => (float) ($godownEntry->amount ?? 0),
            'godownName' => $godown->name,
            'carrierName' => $dispatch?->carrier_name,
            'motorVehicleNo' => $dispatch?->motor_vehicle_no,
            'dispatchedThrough' => $dispatch?->dispatched_through,
            'source' => $dispatch?->source,
            'destination' => $dispatch?->destination,
            'billOfLadingNo' => $dispatch?->bill_of_lading_no,
            'billOfLadingDate' => $dispatch?->bill_of_lading_date?->format('Y-m-d'),
            'receiptDocNo' => $dispatch?->receipt_doc_no,
            'weight' => (float) ($dispatch?->weight ?? 0),
            'volume' => (float) ($dispatch?->volume ?? 0),
            'paymentStatus' => $voucher->payment_status,
        ];
    }

    /**
     * Accumulate a stock journal godown entry for the freight zone-wise report.
     * Uses the freight (sales) voucher for metadata (ID, no, date, party, payment status)
     * and the referenced delivery note for dispatch details and stock journal data.
     */
    private function accumulateFreightZoneEntry(array &$bucket, $freightVoucher, $deliveryNote, $godownEntry, $godown, string $movementType, float $quantity, string $detailKey): void
    {
        $billingQty = (float) ($godownEntry->billing_quantity ?? 0);
        $stockEntry = $godownEntry->stock_journal_entry;
        $stockUnit = $stockEntry?->stock_unit;

        if ($movementType === 'in') {
            $bucket['totalInwardQuantity'] += $quantity;
            $bucket['totalInwardBillingQuantity'] += $billingQty;
        } elseif ($movementType === 'out') {
            $bucket['totalOutwardQuantity'] += $quantity;
            $bucket['totalOutwardBillingQuantity'] += $billingQty;
        }

        $bucket['totalAmount'] += (float) ($godownEntry->amount ?? 0);
        $bucket['_voucher_ids'][] = $freightVoucher->id;

        // Dispatch details come from the delivery note, not the freight voucher
        $dispatch = $deliveryNote->voucher_dispatch_detail;

        $bucket[$detailKey][] = [
            'voucherId' => $freightVoucher->id,
            'voucherNo' => $freightVoucher->voucher_no,
            'voucherDate' => $freightVoucher->voucher_date?->format('Y-m-d'),
            'partyName' => $freightVoucher->voucher_party?->name,
            'itemId' => $stockEntry?->stock_item_id,
            'itemName' => $stockEntry?->stock_item?->name,
            'unitCode' => $stockUnit?->code,
            'unitName' => $stockUnit?->name,
            'noOfDecimalPlaces' => $stockUnit?->no_of_decimal_places,
            'movementType' => $movementType,
            'actualQuantity' => $quantity,
            'billingQuantity' => $billingQty,
            'amount' => (float) ($godownEntry->amount ?? 0),
            'godownName' => $godown->name,
            'carrierName' => $dispatch?->carrier_name,
            'motorVehicleNo' => $dispatch?->motor_vehicle_no,
            'dispatchedThrough' => $dispatch?->dispatched_through,
            'source' => $dispatch?->source,
            'destination' => $dispatch?->destination,
            'billOfLadingNo' => $dispatch?->bill_of_lading_no,
            'billOfLadingDate' => $dispatch?->bill_of_lading_date?->format('Y-m-d'),
            'receiptDocNo' => $dispatch?->receipt_doc_no,
            'weight' => (float) ($dispatch?->weight ?? 0),
            'volume' => (float) ($dispatch?->volume ?? 0),
            'paymentStatus' => $freightVoucher->payment_status,
        ];
    }

    /**
     * Finalize grouped data: compute unique voucher count, closing quantity, remove internal tracking.
     */
    private function finalizeGroupedData(array $groupedData): array
    {
        $result = [];
        foreach ($groupedData as $data) {
            $data['totalEntries'] = count($data['_voucher_ids']);
            $data['totalClosingQuantity'] = $data['totalInwardQuantity'] - $data['totalOutwardQuantity'];
            $data['totalBillingClosingQuantity'] = $data['totalInwardBillingQuantity'] - $data['totalOutwardBillingQuantity'];
            unset($data['_voucher_ids']);
            $result[] = $data;
        }

        return $result;
    }

    public function transporterWiseReport(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        $queryBuilder = Voucher::with($this->defaultResource)
            ->where('vouchers.module', 'freight')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
            ->leftJoin('voucher_references', 'voucher_references.voucher_id', '=', 'vouchers.id')
            ->leftJoin('vouchers as ref_voucher', 'ref_voucher.id', '=', 'voucher_references.ref_voucher_id')
            ->orderBy('vouchers.created_at', 'desc')
            ->select(
                'vouchers.*',
                'ref_voucher.voucher_no as referenced_voucher_no',
                'voucher_references.type as reference_type'
            );

        $vouchers = $queryBuilder->get();

        return $vouchers->map(fn ($voucher) => $this->voucherService->attachLedgerInfo($voucher));

    }

    public function transporterItemWiseReport(): Collection
    {
        // Get freight vouchers with referenced delivery note data including stock journal items and dispatch details
        $freightVouchers = $this->getFreightVouchersWithReferencedData();
        $transporterData = [];

        foreach ($freightVouchers as $freightVoucher) {
            // Each freight voucher references exactly one delivery note via voucher_references
            $reference = $freightVoucher->voucher_references->first();
            if (! $reference) {
                continue;
            }

            $deliveryNote = $reference->reference_voucher;
            if (! $deliveryNote || ! $deliveryNote->stock_journal) {
                continue;
            }

            $dispatch = $deliveryNote->voucher_dispatch_detail;
            $transporterName = $dispatch?->carrier_name ?? 'Unknown';
            $vehicleNumber = $dispatch?->motor_vehicle_no ?? '';
            $source = $dispatch?->source ?? '';
            $destination = $dispatch?->destination ?? '';
            $voucherId = $freightVoucher->id;
            $voucherNo = $freightVoucher->voucher_no;
            $voucherDate = $freightVoucher->voucher_date?->format('Y-m-d');
            $partyName = $freightVoucher->voucher_party?->name ?? $deliveryNote->voucher_party?->name ?? '';
            $paymentStatus = $freightVoucher->payment_status;
            $totalFare = (float) ($dispatch?->total_fare ?? 0);

            $stockJournal = $deliveryNote->stock_journal;

            foreach ($stockJournal->stock_journal_entries as $entry) {
                $itemName = $entry->stock_item?->name ?? 'Unknown';
                $unitCode = $entry->stock_unit?->code ?? '';
                $noOfDecimalPlaces = $entry->stock_unit?->no_of_decimal_places ?? 2;
                $actualQuantity = (float) ($entry->actual_quantity ?? 0);
                $billingQuantity = (float) ($entry->billing_quantity ?? 0);
                $amount = (float) ($entry->amount ?? 0);

                // Group by transporter name
                $transporterKey = $transporterName;

                if (! isset($transporterData[$transporterKey])) {
                    $transporterData[$transporterKey] = [
                        'transporterName' => $transporterName,
                        'vehicleNumber' => $vehicleNumber,
                        'totalVouchers' => 0,
                        'totalQuantity' => 0,
                        'totalAmount' => 0,
                        'entries' => [],
                    ];
                }

                $transporterData[$transporterKey]['totalQuantity'] += $actualQuantity;
                $transporterData[$transporterKey]['totalAmount'] += $amount;

                // Each stock journal entry becomes a flat row with item name visible
                $transporterData[$transporterKey]['entries'][] = [
                    'voucherId' => $voucherId,
                    'voucherNo' => $voucherNo,
                    'voucherDate' => $voucherDate,
                    'partyName' => $partyName,
                    'source' => $source,
                    'destination' => $destination,
                    'vehicleNumber' => $vehicleNumber,
                    'carrierName' => $transporterName,
                    'itemName' => $itemName,
                    'unitCode' => $unitCode,
                    'noOfDecimalPlaces' => $noOfDecimalPlaces,
                    'actualQuantity' => $actualQuantity,
                    'billingQuantity' => $billingQuantity,
                    'amount' => $amount,
                    'paymentStatus' => $paymentStatus,
                    'totalFare' => $totalFare,
                ];
            }
        }

        // Sort entries by voucher within each transporter so items from same invoice stay together
        $result = [];
        foreach ($transporterData as $key => $data) {
            // Sort entries by voucher no so items from same invoice are adjacent
            usort($data['entries'], function ($a, $b) {
                return strcmp($a['voucherNo'] ?? '', $b['voucherNo'] ?? '');
            });

            // Count unique vouchers
            $seenVouchers = [];
            foreach ($data['entries'] as $entry) {
                $seenVouchers[$entry['voucherNo']] = true;
            }
            $data['totalVouchers'] = count($seenVouchers);
            $data['entries'] = array_values($data['entries']);
            $result[] = $data;
        }

        return new Collection($result);
    }

    public function vehicleWiseReport(): Collection
    {
        // Implement the logic for vehicle wise report
        // Return a collection of results
        return collect(); // Placeholder
    }

    public function billingPreferenceReport(): Collection
    {
        // Implement the logic for billing preference report
        // Return a collection of results
        return collect(); // Placeholder
    }

    public function voucherWiseReport(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        $queryBuilder = Voucher::with($this->defaultResource)
            ->where('vouchers.module', 'freight')
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
            ->leftJoin('voucher_references', 'voucher_references.voucher_id', '=', 'vouchers.id')
            ->leftJoin('vouchers as ref_voucher', 'ref_voucher.id', '=', 'voucher_references.ref_voucher_id')
            ->orderBy('vouchers.created_at', 'desc')
            ->select(
                'vouchers.*',
                'ref_voucher.voucher_no as referenced_voucher_no',
                'voucher_references.type as reference_type'
            );

        $vouchers = $queryBuilder->get();

        return $vouchers->map(fn ($voucher) => $this->voucherService->attachLedgerInfo($voucher));
    }

    public function getById(int $id): ?Freight
    {
        return Freight::with($this->defaultResource)->findOrFail($id);
    }

    /**
     * Create a freight (sales) voucher linked to a delivery note.
     *
     * Pipeline flow: validate delivery note → check existing freight →
     * handle duplicates → build voucher data → store → return.
     */
    public function store(array $data): Voucher
    {
        $deliveryNoteId = $data['delivery_note_id'] ?? null;
        if (! $deliveryNoteId) {
            throw new \Exception('Delivery Note ID is required to create Freight record.');
        }

        // The dispatch-detail sync and the sales-voucher creation must be atomic:
        // if voucher creation fails, the dispatch detail must not stay updated.
        // (VoucherService::store() opens its own nested transaction, which Laravel
        // handles via a savepoint inside this one.)
        return DB::transaction(function () use ($data, $deliveryNoteId) {
            // Pipeline Step 1: Fetch and validate the delivery note
            $deliveryNote = $this->validateDeliveryNoteStep($deliveryNoteId);

            // Pipeline Step 2: Persist the freight form's dispatch values (transporter,
            // vehicle, weight, rate, charges, total fare) onto the delivery note's
            // voucher_dispatch_details — otherwise Dispatch Details stay stale and the
            // freight bill is built from outdated fare data.
            $this->syncDispatchDetailFromFreightStep($deliveryNote, $data);

            // Re-read the dispatch detail so the voucher entries and duplicate check
            // below use the fare the user actually entered on the freight screen.
            $dispatchDetail = VoucherDispatchDetail::where('voucher_id', $deliveryNote->id)->first();

            // Pipeline Step 3: Check for existing freight reference (duplicate handling)
            $existingVoucher = $this->checkExistingFreightStep($deliveryNoteId, $dispatchDetail);
            if ($existingVoucher) {
                return $existingVoucher;
            }

            // Pipeline Step 4: Build and store the sales voucher
            return $this->buildAndStoreVoucherStep($deliveryNote, $dispatchDetail, $deliveryNoteId);
        });
    }

    /**
     * Persist the freight bill's dispatch/freight fields onto the delivery note's
     * voucher_dispatch_detail record (create when missing, update when present).
     *
     * The delivery note owns the dispatch details; the freight screen edits the
     * same fields (transporter, vehicle, weight, rate, charges, total fare), so
     * they must be written back here — otherwise the Dispatch Details shown on
     * the delivery note and in the freight reports never reflect the freight bill.
     */
    protected function syncDispatchDetailFromFreightStep(Voucher $deliveryNote, array $data): void
    {
        $existing = $deliveryNote->voucher_dispatch_detail;

        // Fare fields the freight form can legitimately leave at 0 (e.g. when they
        // were entered only via the Dispatch Details dialog). A 0/empty value from
        // the freight form must never clobber a rate/fare already saved on the
        // delivery note — the dialog is the authoritative source for these and the
        // freight bill amount is derived from them.
        $fareFields = [
            'rate',
            'total_fare',
            'freight_charges',
            'discount',
            'loading_charges',
            'unloading_charges',
            'packing_charges',
            'insurance_charges',
            'other_charges',
        ];

        $dispatchData = array_filter([
            'carrier_name' => $data['transporter'] ?? null,
            'motor_vehicle_no' => $data['vehicle_number'] ?? null,
            'source' => $data['source'] ?? null,
            'destination' => $data['destination'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'weight' => $data['weight'] ?? null,
            'weight_unit_id' => $data['weight_unit_id'] ?? null,
            'volume' => $data['volume'] ?? null,
            'volume_unit_id' => $data['volume_unit_id'] ?? null,
            'distance' => $data['distance'] ?? null,
            'distance_unit_id' => $data['distance_unit_id'] ?? null,
            'freight_basis' => $data['freight_basis'] ?? null,
            'rate' => $data['rate'] ?? null,
            'rate_unit_id' => $data['rate_unit_id'] ?? null,
            'loading_charges' => $data['loading_charges'] ?? null,
            'unloading_charges' => $data['unloading_charges'] ?? null,
            'packing_charges' => $data['packing_charges'] ?? null,
            'insurance_charges' => $data['insurance_charges'] ?? null,
            'other_charges' => $data['other_charges'] ?? null,
            'freight_charges' => $data['freight_charges'] ?? null,
            'total_fare' => $data['total_fare'] ?? null,
            'discount' => $data['discount'] ?? null,
        ], function ($value, $key) use ($existing, $fareFields) {
            if ($value === null || $value === '') {
                return false;
            }

            // Preserve an existing non-zero fare when the freight form sends 0 for
            // a fare field (the form's rate defaults to 0 when never touched).
            if (in_array($key, $fareFields, true)
                && (float) $value == 0
                && $existing
                && (float) ($existing->{$key} ?? 0) > 0
            ) {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($dispatchData)) {
            return;
        }

        $dispatchData['voucher_id'] = $deliveryNote->id;

        $rules = (new VoucherDispatchDetailRequest)->rules();
        $validated = Validator::make($dispatchData, $rules)->validate();

        if ($existing) {
            $this->voucherDispatchDetailService->update($validated, $existing->id);
        } else {
            $this->voucherDispatchDetailService->store($validated);
        }
    }

    /**
     * Step 1: Validate the delivery note exists and return it.
     */
    protected function validateDeliveryNoteStep(int $deliveryNoteId): Voucher
    {
        $deliveryNote = $this->voucherService->getById($deliveryNoteId);
        if (! $deliveryNote) {
            throw new \Exception('Delivery Note not found with ID: '.$deliveryNoteId);
        }

        return $deliveryNote;
    }

    /**
     * Step 2: Check if a freight record already exists for this delivery note.
     * If the amount matches, return the existing voucher (idempotent).
     * If the amount differs, delete the old one and allow re-creation.
     * Returns the existing voucher or null if no conflict.
     */
    protected function checkExistingFreightStep(int $deliveryNoteId, $dispatchDetail): ?Voucher
    {
        $deliverNoteAsReferences = $this->voucherReferenceService->getByReferenceVoucherId($deliveryNoteId);

        $existingFreightReference = $deliverNoteAsReferences->first(function ($reference) {
            $refVoucher = $this->voucherService->getById($reference->voucher_id);

            return $refVoucher && $refVoucher->module === 'freight'
                && $refVoucher->voucher_type_id === $this->salesVoucherTypeID;
        });

        if (! $existingFreightReference) {
            return null;
        }

        $salesVoucher = $this->voucherService->getById($existingFreightReference->voucher_id);

        // If amount matches latest dispatch detail, return existing as idempotent response
        if ($salesVoucher->amount == $dispatchDetail->total_fare) {
            return $salesVoucher->load('company');
        }

        // Amount changed — delete old sales voucher and associated records
        $salesVoucher->voucher_entries->each(fn ($entry) => $entry->delete());
        $salesVoucher->voucher_references->each(fn ($reference) => $reference->delete());
        $salesVoucher->delete();

        return null;
    }

    /**
     * Step 3: Build the sales voucher payload and store it.
     */
    protected function buildAndStoreVoucherStep(Voucher $deliveryNote, $dispatchDetail, int $deliveryNoteId): Voucher
    {
        $salesAccountLedger = $this->accountLedgerService->getById($this->salesAccountLedgerID);
        if (! $salesAccountLedger) {
            throw new \Exception('Sales Account Ledger not found with ID: '.$this->salesAccountLedgerID);
        }

        $salesVoucherData = [
            'voucher_type_id' => $this->salesVoucherTypeID,
            'voucher_date' => date('Y-m-d'),
            'company_id' => $deliveryNote->company_id,
            'fiscal_year_id' => $deliveryNote->fiscal_year_id,
            'module' => 'freight',
            'reference_no' => $deliveryNote->voucher_no,
            'reference_date' => $deliveryNote->voucher_date,
            'remarks' => 'being the payment received towards freight charges pertaining to Delivery Note ID : '
                .$deliveryNoteId.' dated '.date_format($deliveryNote->voucher_date, 'd-M-Y'),
            'party_ledger' => $deliveryNote->party_ledger,
            'transaction_ledger' => [
                'id' => $salesAccountLedger->id,
                'name' => $salesAccountLedger->name,
                'code' => $salesAccountLedger->code,
                'account_group_id' => $salesAccountLedger->account_group_id,
            ],
            'voucher_entries' => [
                [
                    'entry_order' => 1,
                    'account_ledger_id' => $salesAccountLedger->id,
                    'debit' => 0,
                    'credit' => $dispatchDetail->total_fare ?? 0,
                ],
                [
                    'entry_order' => 2,
                    'account_ledger_id' => $deliveryNote->party_ledger['id'],
                    'debit' => $dispatchDetail->total_fare ?? 0,
                    'credit' => 0,
                ],
            ],
            'voucher_reference' => [
                'ref_voucher_id' => $deliveryNoteId,
                'type' => 'freight',
            ],
        ];

        $voucherRules = (new VoucherRequest)->rules();
        $validatedVoucherData = Validator::make($salesVoucherData, $voucherRules)->validate();

        $salesVoucherStored = $this->voucherService->store($validatedVoucherData);
        $salesVoucher = $this->voucherService->getById($salesVoucherStored->id);

        return $salesVoucher->load('company');
    }

    /**
     * Create a receipt voucher for a freight payment received against a delivery note.
     *
     * Modeled on buildAndStoreVoucherStep(): the delivery note is the source for
     * company/fiscal year, party ledger and reference info, and the voucher is
     * validated against VoucherRequest rules before being stored.
     *
     * The receipt is linked back to the delivery note with a `freight_payment`
     * reference (the same type used by the freight payment lookups in
     * ReceiptVoucherService / PaymentService), so it is recognised as a payment.
     *
     * Expected input keys:
     * - delivery_note_id (required)
     * - transaction_ledger_id (required) — cash/bank ledger receiving the money
     * - amount (optional, defaults to the dispatch detail total_fare)
     * - receipt_date, payment_mode, remarks (optional)
     */
    public function payment_voucher(array $data): Voucher
    {
        $deliveryNoteId = $data['delivery_note_id'] ?? null;
        if (! $deliveryNoteId) {
            throw new \Exception('Delivery Note ID is required to create a freight receipt voucher.');
        }

        $deliveryNote = $this->voucherService->getById($deliveryNoteId);
        if (! $deliveryNote instanceof Voucher) {
            throw new \Exception('Delivery Note not found with ID: '.$deliveryNoteId);
        }

        // The cash/bank ledger receiving the payment (from the freight payment form)
        $transactionLedgerId = $data['transaction_ledger_id'] ?? null;
        $transactionLedger = $transactionLedgerId
            ? $this->accountLedgerService->getById((int) $transactionLedgerId)
            : null;
        if (! $transactionLedger instanceof AccountLedger) {
            throw new \Exception('Transaction (cash/bank) ledger is required to create the freight receipt voucher.');
        }

        // Default to the full dispatch fare unless a (partial) amount is provided
        $amount = $data['amount'] ?? null;
        if ($amount === null || $amount === '') {
            $dispatchDetail = VoucherDispatchDetail::where('voucher_id', $deliveryNote->id)->first();
            $amount = $dispatchDetail->total_fare ?? 0;
        }
        $amount = (float) $amount;

        $receiptVoucherData = [
            'voucher_type_id' => $this->receiptVoucherTypeID,
            'voucher_date' => $data['receipt_date'] ?? date('Y-m-d'),
            'company_id' => $deliveryNote->company_id,
            'fiscal_year_id' => $deliveryNote->fiscal_year_id,
            'module' => 'receipt_voucher',
            'reference_no' => $deliveryNote->voucher_no,
            'reference_date' => $deliveryNote->voucher_date,
            'remarks' => $data['remarks'] ?? $data['remark'] ?? 'being the payment received towards freight charges pertaining to Delivery Note ID : '
                .$deliveryNoteId.' dated '.date_format($deliveryNote->voucher_date, 'd-M-Y'),
            'payment_mode' => $data['payment_mode'] ?? null,
            'party_ledger' => $deliveryNote->party_ledger,
            'transaction_ledger' => [
                'id' => $transactionLedger->id,
                'name' => $transactionLedger->name,
                'code' => $transactionLedger->code,
                'account_group_id' => $transactionLedger->account_group_id,
            ],
            'voucher_entries' => [
                [
                    'entry_order' => 1,
                    'account_ledger_id' => $transactionLedger->id,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'entry_order' => 2,
                    'account_ledger_id' => $deliveryNote->party_ledger['id'],
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
            'voucher_reference' => [
                'ref_voucher_id' => $deliveryNoteId,
                'type' => 'freight_payment',
            ],
        ];

        $voucherRules = (new VoucherRequest)->rules();
        $validatedVoucherData = Validator::make($receiptVoucherData, $voucherRules)->validate();

        $receiptVoucherStored = $this->voucherService->store($validatedVoucherData);
        $receiptVoucher = $this->voucherService->getById($receiptVoucherStored->id);

        return $receiptVoucher->load('company');
    }

    public function update(array $data, int $id): Freight
    {
        $record = Freight::findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = Freight::findOrFail($id);

        return $record->delete();
    }
}
