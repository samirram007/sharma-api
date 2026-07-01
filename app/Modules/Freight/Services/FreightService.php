<?php

namespace Modules\Freight\Services;

use Modules\AccountLedger\Contracts\AccountLedgerServiceInterface;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\AccountLedger\Services\AccountLedgerService;
use Modules\Freight\Contracts\FreightServiceInterface;
use Modules\Freight\Models\Freight;
use Modules\Godown\Contracts\GodownServiceInterface;
use Modules\Godown\Models\Godown;
use Modules\Voucher\Contracts\VoucherServiceInterface;
use Modules\Voucher\Models\Voucher;
use Modules\Voucher\Requests\VoucherRequest;
use Modules\VoucherDispatchDetail\Contracts\VoucherDispatchDetailServiceInterface;
use Modules\VoucherDispatchDetail\Requests\VoucherDispatchDetailRequest;
use Modules\VoucherDispatchDetail\Services\VoucherDispatchDetailService;
use Modules\VoucherReference\Contracts\VoucherReferenceServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class FreightService implements FreightServiceInterface
{
    protected $resource = [];
    protected $deliverNoteVoucherTypeID = 2001; //delivery note
    protected $salesVoucherTypeID = 1006; //sales voucher
    protected $receiptVoucherTypeID = 1003;
    protected $salesAccountLedgerID = 3000001; //sales account ledger id
    function __construct(
        protected AccountLedgerServiceInterface $accountLedgerService,
        protected VoucherServiceInterface $voucherService,
        protected VoucherReferenceServiceInterface $voucherReferenceService,
        protected VoucherDispatchDetailServiceInterface $voucherDispatchDetailService,
        protected GodownServiceInterface $godownService
    ) {
        $this->resource = [
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
        return Freight::with($this->resource)->get();
    }


    public function getDeliveryNote(int $page=1, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        $query = Voucher::with($this->resource)
            ->where('vouchers.voucher_type_id', $this->deliverNoteVoucherTypeID)
            ->whereNotNull('vouchers.stock_journal_id')
            // Fiscal year period scope (uses user's custom reporting period from user_fiscal_years)
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate]);

        // Apply freight_status filter (pending = no freight bill yet, prepared = has freight bill, all = both)
        $this->applyFreightStatusFilter($query, $filters);

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('vouchers.voucher_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('vouchers.voucher_date', '<=', $filters['date_to']);
        }

        // Search filter - searches across multiple fields
        if (!empty($filters['search'])) {
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
        $vouchers->getCollection()->transform(fn($voucher) => $this->voucherService->attachLedgerInfo($voucher));

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

        if (!empty($filters['date_from'])) {
            $query->where('vouchers.voucher_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('vouchers.voucher_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
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
     * - 'prepared': Only delivery notes that already have a freight bill
     * - 'pending' (default): Only delivery notes without a freight bill
     * - 'all' or any other value: No filtering on freight status
     *
     * Matches the same logic used in store() to detect existing freight bills:
     * A freight bill exists when a voucher_reference has ref_voucher_id = delivery note ID
     * and the referencing voucher (voucher_id) has module='freight' and voucher_type_id=1006.
     */
    private function applyFreightStatusFilter($query, array $filters): void
    {
        $status = $filters['freight_status'] ?? 'pending';

        // Build the subquery to find delivery note IDs that have a freight bill referencing them.
        // This mirrors the logic in store() that checks:
        //   voucher_references.ref_voucher_id = delivery_note_id
        //   JOIN vouchers ON voucher_references.voucher_id = vouchers.id
        //   WHERE vouchers.module = 'freight' AND vouchers.voucher_type_id = 1006
        $freightedDnIdsQuery = function ($q) {
            $q->select('vr.ref_voucher_id')
                ->from('voucher_references as vr')
                ->join('vouchers as v', 'vr.voucher_id', '=', 'v.id')
                ->where('v.module', 'freight')
                ->where('v.voucher_type_id', $this->salesVoucherTypeID);
        };

        if ($status === 'pending') {
            // Exclude delivery notes that already have a freight bill
            $query->whereNotIn('vouchers.id', $freightedDnIdsQuery);
        } elseif ($status === 'prepared') {
            // Only delivery notes that HAVE a freight bill
            $query->whereIn('vouchers.id', $freightedDnIdsQuery);
        }
        // 'all' — no additional filtering
    }

    public function godownWiseReport(): Collection
    {
        $deliveryNotes = $this->getDeliveryNotesWithStockJournals();
        $godownData = [];

        $this->processStockJournalEntries($deliveryNotes, function ($voucher, $godownEntry, $godown, $movementType, $quantity) use (&$godownData) {
            $key = (string) $godown->id;

            if (!isset($godownData[$key])) {
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
        $zones = Godown::where('storage_unit_type', 'zone')->get()->keyBy('id');
        $zoneIds = $zones->keys()->toArray();

        // Get freight (sales) vouchers instead of delivery notes
        $freightVouchers = $this->getFreightVouchersWithReferencedData();
        $zoneData = [];

        foreach ($freightVouchers as $freightVoucher) {
            // Each freight voucher references exactly one delivery note via voucher_references
            $reference = $freightVoucher->voucher_references->first();
            if (!$reference) {
                continue;
            }

            $deliveryNote = $reference->reference_voucher;
            if (!$deliveryNote || !$deliveryNote->stock_journal) {
                continue;
            }

            $stockJournal = $deliveryNote->stock_journal;

            foreach ($stockJournal->stock_journal_entries as $entry) {
                foreach ($entry->stock_journal_godown_entries as $godownEntry) {
                    $godown = $godownEntry->godown;
                    if (!$godown) {
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

                    if (!isset($zoneData[$key])) {
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
        $zones = Godown::where('storage_unit_type', 'zone')->get()->keyBy('id');
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

            if (!isset($zoneData[$key])) {
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
        if (!$zoneId && !$godownId) {
            return new Collection();
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
            if (!in_array($godown->id, $filteredGodownIds)) {
                return;
            }

            $key = (string) $godown->id;

            if (!isset($godownData[$key])) {
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

        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
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
            if (!$stockJournal) {
                continue;
            }

            foreach ($stockJournal->stock_journal_entries as $entry) {
                foreach ($entry->stock_journal_godown_entries as $godownEntry) {
                    $godown = $godownEntry->godown;
                    if (!$godown) {
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

        $queryBuilder = Voucher::with($this->resource)
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


        return $vouchers->map(fn($voucher) => $this->voucherService->attachLedgerInfo($voucher));

    }

    public function transporterItemWiseReport(): Collection
    {
        // Get freight vouchers with referenced delivery note data including stock journal items and dispatch details
        $freightVouchers = $this->getFreightVouchersWithReferencedData();
        $transporterData = [];

        foreach ($freightVouchers as $freightVoucher) {
            // Each freight voucher references exactly one delivery note via voucher_references
            $reference = $freightVoucher->voucher_references->first();
            if (!$reference) {
                continue;
            }

            $deliveryNote = $reference->reference_voucher;
            if (!$deliveryNote || !$deliveryNote->stock_journal) {
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

                if (!isset($transporterData[$transporterKey])) {
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

        $queryBuilder = Voucher::with($this->resource)
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


        return $vouchers->map(fn($voucher) => $this->voucherService->attachLedgerInfo($voucher));
    }



    public function getById(int $id): ?Freight
    {
        return Freight::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): Voucher
    {

        $deliverNoteId = $data['delivery_note_id'] ?? null;
        if ($deliverNoteId) {
            $deliveryNote = $this->voucherService->getById($deliverNoteId);
            if (!$deliveryNote) {
                throw new \Exception("Delivery Note not found with ID: " . $deliverNoteId);
            }


            // $dispatchDetailData = [
            //     'voucher_id' => $deliverNoteId,
            //     'distance' => $data['distance'] ?? null,
            //     'rate' => $data['rate'] ?? null,
            //     'distance_unit_id' => $data['distance_unit_id'] ?? null,
            //     'rate_unit_id' => $data['rate_unit_id'] ?? null,
            //     'quantity' => $data['quantity'] ?? null,
            //     'weight' => $data['weight'] ?? null,
            //     'weight_unit_id' => $data['weight_unit_id'] ?? null,
            //     'volume' => $data['volume'] ?? null,
            //     'volume_unit_id' => $data['volume_unit_id'] ?? null,
            //     'loading_charges' => $data['loading_charges'] ?? null,
            //     'unloading_charges' => $data['unloading_charges'] ?? null,
            //     'packing_charges' => $data['packing_charges'] ?? null,
            //     'insurance_charges' => $data['insurance_charges'] ?? null,
            //     'other_charges' => $data['other_charges'] ?? null,
            //     'freight_charges' => $data['freight_charges'] ?? null,
            //     'total_fare' => $data['total_fare'] ?? null,
            // ];
            $dispatchDetail = $deliveryNote->voucher_dispatch_detail;
            // $rules = (new VoucherDispatchDetailRequest())->rules();
            // // dump($rules);
            // $validatedDispatchData = Validator::make($dispatchDetailData, $rules)->validate();

            // //  dd($validatedDispatchData);
            // if (!empty($validatedDispatchData)) {
            //     if (!$dispatchDetail) {

            //         $dispatchDetail = $this->voucherDispatchDetailService->store($validatedDispatchData);
            //         //dd("VoucherLevel", $stockJournal);
            //         $data['stock_journal_id'] = $stockJournal->id ?? null;

            //     } else {
            //         $dispatchDetail = $this->voucherDispatchDetailService->update($validatedDispatchData, $dispatchDetail->id);
            //     }
            // }



            $deliverNoteAsReferences = $this->voucherReferenceService->getByReferenceVoucherId($deliverNoteId);


            $existingFreightReference = $deliverNoteAsReferences->first(function ($reference) {
                $refVoucher = $this->voucherService->getById($reference->voucher_id);
                return $refVoucher && $refVoucher->module === 'freight'
                    && $refVoucher->voucher_type_id === $this->salesVoucherTypeID;
            });

            if ($existingFreightReference) {
                $salesVoucher = $this->voucherService->getById($existingFreightReference->voucher_id);
                if ($salesVoucher->amount == $dispatchDetail->total_fare) {
                    return $salesVoucher->load('company');
                }

                $salesVoucher->voucher_entries->each(function ($entry) {
                    $entry->delete();
                });
                $salesVoucher->voucher_references->each(function ($reference) {
                    $reference->delete();
                });
                $salesVoucher->delete();
                // return $salesVoucher->load('company');
                //throw new \Exception("A Freight record is already associated with this Delivery Note ID: " . $deliverNoteId);
            }


            // Create a new Sales Voucher linked to this Delivery Note
            $salesAccountLedger = $this->accountLedgerService->getById($this->salesAccountLedgerID);
            if (!$salesAccountLedger) {
                throw new \Exception("Sales Account Ledger not found with ID: " . $this->salesAccountLedgerID);
            }


            //being the payment received towards freight charges pertaining to Delivery Note ID 24
            $salesVoucherData = [
                'voucher_type_id' => $this->salesVoucherTypeID,
                'voucher_date' => date('Y-m-d'),
                'company_id' => $deliveryNote->company_id,
                'fiscal_year_id' => $deliveryNote->fiscal_year_id,
                'module' => 'freight',
                'reference_no' => $deliveryNote->voucher_no,
                'reference_date' => $deliveryNote->voucher_date,
                'remarks' => 'being the payment received towards freight charges pertaining to Delivery Note ID : ' . $deliverNoteId . ' dated ' . date_format($deliveryNote->voucher_date, 'd-M-Y'),
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
                    'ref_voucher_id' => $deliverNoteId,
                    'type' => 'freight'
                ],
                // Add other necessary fields here
            ];
            $voucherRules = (new VoucherRequest())->rules();
            // dump($rules);
            $validatedVoucherData = Validator::make($salesVoucherData, $voucherRules)->validate();
            // dd($validatedVoucherData);
            //check if

            $salesVoucherStored = $this->voucherService->store($validatedVoucherData);
            $salesVoucher = $this->voucherService->getById($salesVoucherStored->id);
            return $salesVoucher->load('company');
        }
        throw new \Exception("Delivery Note ID is required to create Freight record.");
    }

    public function payment_voucher(array $data): Collection
    {
        $deliverNoteId = $data['delivery_note_id'] ?? null;
        if ($deliverNoteId) {
            $deliveryNote = $this->voucherService->getById($deliverNoteId);
            if (!$deliveryNote) {
                throw new \Exception("Delivery Note not found with ID: " . $deliverNoteId);
            }
        }

        $receiptVoucherData = [
            'voucher_type_id' => $this->receiptVoucherTypeID,
        ];

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
