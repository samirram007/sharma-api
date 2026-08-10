<?php

namespace Modules\StockSummary\Services;

use App\Enums\MovementType;
use App\Support\Services\BaseService;
use App\Support\Traits\HasItemAverageRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockJournal\Models\StockJournal;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\StockSummary\Models\StockSummary;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;
use Modules\Voucher\Models\Voucher;

class StockSummaryService extends BaseService implements StockSummaryServiceInterface
{
    use HasItemAverageRate;

    protected string $modelClass = StockSummary::class;

    protected array $defaultResource = [];

    protected $userFiscalYearService;

    protected $userFiscalYear;

    public function __construct(UserFiscalYearServiceInterface $userFiscalYearService)
    {
        $this->userFiscalYearService = $userFiscalYearService;
        // auth()->id() is null outside HTTP (CLI / queue / tests) — guard so the
        // service can be constructed without an authenticated user.
        $userId = auth()->id();
        $this->userFiscalYear = $userId
            ? $this->userFiscalYearService->getByUserId($userId)
            : null;
    }

    /**
     * The closing-stock "as of" date — the user's reporting-period end date when
     * set, otherwise the fiscal year end (full-year view). Normalized to a Carbon
     * so downstream ?Carbon hints and date formatting are safe.
     */
    protected function getAsOfDate(?FiscalYear $fiscalYear = null): ?Carbon
    {
        // Self-resolve the fiscal year when the caller didn't pass one, so every
        // caller shares the same fallback chain (reporting-period end, else FY end).
        $fiscalYear ??= FiscalYear::find($this->userFiscalYear?->fiscal_year_id);

        $asOfDate = $this->userFiscalYear?->end_date ?? $fiscalYear?->end_date;

        return $asOfDate ? Carbon::parse($asOfDate) : null;
    }

    /**
     * Closing Stock — as of the current fiscal year.
     *
     * If a closing stock journal (CLSSK voucher) already exists for the fiscal year,
     * the frozen closing entries are returned (source: 'closing_journal'). Otherwise the
     * closing stock is computed live from the stock movements (source: 'running'), using
     * the same net-quantity-per-item/godown/batch logic and weighted average rates that
     * FiscalYearCloseService::createClosingStockVoucher() uses.
     */
    public function closingStock(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $asOfDate = $this->getAsOfDate($fiscalYear);

        // Look for an existing closing stock journal (CLSSK voucher) for this FY
        $closingVoucher = Voucher::where('fiscal_year_id', $fiscalYearId)
            ->whereHas('voucher_type', fn ($q) => $q->where('code', 'CLSSK'))
            ->with([
                'stock_journal.stock_journal_entries.stock_item.stock_unit',
                'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
            ])
            ->first();

        if ($closingVoucher?->stock_journal) {
            // Frozen closing journal — always shown as-is (FY-end freeze).
            $items = $this->buildClosingStockFromJournal($closingVoucher->stock_journal);
            $source = 'closing_journal';
        } else {
            $items = $this->buildRunningClosingStock($fiscalYearId, $asOfDate);
            $source = 'running';
        }

        $totalQuantity = array_sum(array_column($items, 'closing_quantity'));
        $totalAmount = array_sum(array_column($items, 'closing_amount'));

        return [
            'source' => $source,
            'as_of_date' => $source === 'running' ? $asOfDate?->format('Y-m-d') : null,
            'closing_voucher_id' => $closingVoucher?->id,
            'closing_voucher_no' => $closingVoucher?->voucher_no,
            'closing_date' => $closingVoucher?->voucher_date?->format('Y-m-d'),
            'fiscal_year' => $fiscalYear?->only(['id', 'name', 'start_date', 'end_date']),
            'total_items' => count($items),
            'total_quantity' => round($totalQuantity, 4),
            'total_amount' => round($totalAmount, 2),
            'items' => $items,
        ];
    }

    /**
     * Running closing stock for a specific fiscal year — net balance per item,
     * godown and batch computed live from stock movements (the same calculation
     * the fiscal year close uses to freeze closing stock), valued at the item's
     * weighted average inward rate.
     *
     * Used as a fallback when no frozen CLSSK closing journal exists for the
     * fiscal year (e.g. the previous year was never closed), so opening stock
     * can still be pre-filled from the last year's running balance.
     */
    public function runningClosingStockItems(int $fiscalYearId, ?Carbon $asOfDate = null): array
    {
        return $this->buildRunningClosingStock($fiscalYearId, $asOfDate);
    }

    /**
     * Build the closing stock item tree (item → godown → batch) from a frozen
     * CLOSING stock journal created by FiscalYearClose.
     */
    protected function buildClosingStockFromJournal(StockJournal $journal): array
    {
        $items = [];

        foreach ($journal->stock_journal_entries as $entry) {
            $item = $entry->stock_item;
            if (! $item) {
                continue;
            }

            $godownGroups = $entry->stock_journal_godown_entries->groupBy('godown_id');
            $godownDetails = [];
            $itemQty = 0;
            $itemAmount = 0;

            foreach ($godownGroups as $godownId => $godownEntries) {
                $first = $godownEntries->first();
                $qty = (float) $godownEntries->sum('actual_quantity');
                $amount = (float) $godownEntries->sum('amount');

                $batchDetails = $godownEntries
                    ->map(fn ($ge) => [
                        'batch_no' => $ge->batch_no,
                        'mfg_date' => $ge->mfg_date?->format('Y-m-d'),
                        'expiry_date' => $ge->expiry_date?->format('Y-m-d'),
                        'quantity' => (float) $ge->actual_quantity,
                        'amount' => (float) $ge->amount,
                        'rate' => $ge->rate !== null ? (float) $ge->rate : null,
                    ])
                    ->values()
                    ->toArray();

                $godownDetails[] = [
                    'godown_id' => $first->godown_id,
                    'godown_name' => $first->godown?->name,
                    'godown_code' => $first->godown?->code,
                    'closing_quantity' => $qty,
                    'closing_amount' => $amount,
                    'batch_details' => $batchDetails,
                ];

                $itemQty += $qty;
                $itemAmount += $amount;
            }

            $items[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_code' => $item->stock_unit?->code,
                'unit_name' => $item->stock_unit?->name,
                'no_of_decimal_places' => $item->stock_unit?->no_of_decimal_places ?? 2,
                'closing_quantity' => $itemQty,
                'closing_amount' => $itemAmount,
                'rate' => $itemQty > 0 ? round($itemAmount / $itemQty, 2) : null,
                'godown_details' => $godownDetails,
            ];
        }

        return $items;
    }

    /**
     * Compute the running closing stock for the fiscal year — net quantity per
     * item per godown per batch from the stock movements (same calculation the
     * fiscal year close uses to freeze closing stock), valued at the item's
     * weighted average inward rate.
     *
     * NOTE: unlike FiscalYearCloseService::createClosingStockVoucher(), this
     * respects the not_purged scope and excludes purged godown entries — kept in
     * sync with the other stock reports. If a running preview must match the
     * freeze exactly, both queries should be aligned.
     */
    protected function buildRunningClosingStock(int $fiscalYearId, ?Carbon $asOfDate = null): array
    {
        $stockData = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
            ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
            ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
            ->leftJoin('godowns as g', 'sjge.godown_id', '=', 'g.id')
            ->where('v.fiscal_year_id', $fiscalYearId)
            // Only include movements up to the as-of date (reporting period end)
            ->when($asOfDate, fn ($q) => $q->where('v.voucher_date', '<=', $asOfDate))
            // Respect Eloquent's not_purged global scope
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_godown_entry_purges')
                    ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
            })
            ->selectRaw('
                sje.stock_item_id,
                si.name as item_name,
                su.code as unit_code,
                su.name as unit_name,
                su.no_of_decimal_places,
                sjge.godown_id,
                g.name as godown_name,
                g.code as godown_code,
                sjge.batch_no,
                sjge.mfg_date,
                sjge.expiry_date,
                SUM(CASE
                    WHEN sjge.movement_type = ? THEN sjge.actual_quantity
                    ELSE -sjge.actual_quantity
                END) as net_quantity
            ', [MovementType::IN->value])
            ->groupBy(
                'sje.stock_item_id',
                'si.name',
                'su.code',
                'su.name',
                'su.no_of_decimal_places',
                'sjge.godown_id',
                'g.name',
                'g.code',
                'sjge.batch_no',
                'sjge.mfg_date',
                'sjge.expiry_date'
            )
            ->having('net_quantity', '!=', 0)
            ->get();

        $groupedByItem = $stockData->groupBy('stock_item_id');
        $items = [];

        foreach ($groupedByItem as $itemId => $rows) {
            $first = $rows->first();
            $avgRate = $this->getItemAverageRate((int) $itemId, $fiscalYearId, $asOfDate);

            $godownGroups = $rows->groupBy('godown_id');
            $godownDetails = [];
            $itemQty = 0;
            $itemAmount = 0;

            foreach ($godownGroups as $godownId => $godownRows) {
                $firstGodown = $godownRows->first();
                $godownQty = (float) $godownRows->sum(fn ($r) => abs((float) $r->net_quantity));
                $godownAmount = round($avgRate * $godownQty, 2);

                $batchDetails = $godownRows
                    ->map(fn ($row) => [
                        'batch_no' => $row->batch_no,
                        'mfg_date' => $this->formatDbDate($row->mfg_date),
                        'expiry_date' => $this->formatDbDate($row->expiry_date),
                        'quantity' => abs((float) $row->net_quantity),
                        'amount' => round($avgRate * abs((float) $row->net_quantity), 2),
                        'rate' => $avgRate > 0 ? $avgRate : null,
                    ])
                    ->values()
                    ->toArray();

                $godownDetails[] = [
                    'godown_id' => $firstGodown->godown_id,
                    'godown_name' => $firstGodown->godown_name,
                    'godown_code' => $firstGodown->godown_code,
                    'closing_quantity' => $godownQty,
                    'closing_amount' => $godownAmount,
                    'batch_details' => $batchDetails,
                ];

                $itemQty += $godownQty;
                $itemAmount += $godownAmount;
            }

            $items[] = [
                'item_id' => (int) $itemId,
                'item_name' => $first->item_name,
                'unit_code' => $first->unit_code,
                'unit_name' => $first->unit_name,
                'no_of_decimal_places' => (int) ($first->no_of_decimal_places ?? 2),
                'closing_quantity' => $itemQty,
                'closing_amount' => $itemAmount,
                'rate' => $avgRate > 0 ? $avgRate : null,
                'godown_details' => $godownDetails,
            ];
        }

        // Sort by item name for consistent ordering
        usort($items, fn ($a, $b) => strcmp($a['item_name'], $b['item_name']));

        return $items;
    }

    /**
     * Normalize a raw DB date value to Y-m-d, returning null for empty/zero dates.
     */
    protected function formatDbDate($value): ?string
    {
        if (! $value || $value === '0000-00-00') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * Stock In Hand — item-level summary
     * Formula: Closing Quantity = Opening Quantity + Operating Inward - Operating Outward
     */
    public function stockInHand(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        $items = StockItem::withWhereHas(
            'stock_journal_entries.stock_journal.voucher',
            fn ($q) => $q->where('fiscal_year_id', $fiscalYearId)
                ->when($asOfDate, fn ($query) => $query->where('voucher_date', '<=', $asOfDate))
        )
            ->with([
                'stock_unit',
                'stock_journal_entries' => function ($q) use ($fiscalYearId, $asOfDate) {
                    $q->whereHas(
                        'stock_journal.voucher',
                        fn ($v) => $v->where('fiscal_year_id', $fiscalYearId)
                            ->when($asOfDate, fn ($query) => $query->where('voucher_date', '<=', $asOfDate))
                    )
                        ->with([
                            'stock_journal',
                        ]);
                },
            ])
            ->get();

        $result = [];
        foreach ($items as $index => $item) {
            $stock = $this->calculateItemOpeningAndOperating($item->stock_journal_entries);

            $result[$index]['item_id'] = $item->id;
            $result[$index]['item_name'] = $item->name;
            $result[$index]['unit_code'] = $item->stock_unit ? $item->stock_unit->code : null;
            $result[$index]['unit_name'] = $item->stock_unit ? $item->stock_unit->name : null;
            $result[$index]['no_of_decimal_places'] = $item->stock_unit ? $item->stock_unit->no_of_decimal_places : null;
            $result[$index]['opening_quantity'] = $stock['opening'];
            $result[$index]['opening_amount'] = $stock['opening_amount'];
            $result[$index]['inward_quantity'] = $stock['operating_in'];
            $result[$index]['inward_amount'] = $stock['operating_in_amount'];
            $result[$index]['outward_quantity'] = $stock['operating_out'];
            $result[$index]['outward_amount'] = $stock['operating_out_amount'];
            $result[$index]['closing_quantity'] = $stock['closing'];
            $result[$index]['closing_amount'] = $stock['closing_amount'];
        }

        return $result;
    }

    /**
     * Stock In Hand — item-wise with godown breakdown
     */
    public function stock_in_hand_item_wise(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        $items = StockItem::with([
            'stock_unit',
            'stock_journal_entries' => function ($query) use ($fiscalYearId, $asOfDate) {
                $query->whereHas('stock_journal.voucher', function ($q) use ($fiscalYearId, $asOfDate) {
                    $q->where('fiscal_year_id', $fiscalYearId)
                        ->when($asOfDate, fn ($query) => $query->where('voucher_date', '<=', $asOfDate));
                })
                    ->with([
                        'stock_journal_godown_entries.godown',
                        'stock_journal',
                    ]);
            },
        ])->get();

        $result = [];

        foreach ($items as $item) {
            $allEntries = $item->stock_journal_entries;

            // Separate opening vs operating entries
            [$openingEntries, $operatingEntries] = $this->separateOpeningAndOperating($allEntries);

            // Item-level totals
            $itemOpening = $this->sumMovement($openingEntries, 'in');
            $itemOperatingIn = $this->sumMovement($operatingEntries, 'in');
            $itemOperatingOut = $this->sumMovement($operatingEntries, 'out');
            $itemClosing = $itemOpening + $itemOperatingIn - $itemOperatingOut;

            $itemOpeningAmount = $this->sumMovementAmount($openingEntries, 'in');
            $itemOperatingInAmount = $this->sumMovementAmount($operatingEntries, 'in');
            $itemOperatingOutAmount = $this->sumMovementAmount($operatingEntries, 'out');
            $itemClosingAmount = $itemOpeningAmount + $itemOperatingInAmount - $itemOperatingOutAmount;

            // Godown-level breakdown
            $godownCollection = [];

            // Process opening entries by godown
            $allGodownEntries = $allEntries
                ->flatMap(fn ($e) => $e->stock_journal_godown_entries)
                ->groupBy('godown_id');

            foreach ($allGodownEntries as $godownId => $entries) {
                [$openingGodown, $operatingGodown] = $this->separateOpeningAndOperatingGodown($entries);

                $openingQty = $this->sumMovement($openingGodown, 'in');
                $operatingIn = $this->sumMovement($operatingGodown, 'in');
                $operatingOut = $this->sumMovement($operatingGodown, 'out');
                $closingQty = $openingQty + $operatingIn - $operatingOut;

                $openingAmt = $this->sumMovementAmount($openingGodown, 'in');
                $operatingInAmt = $this->sumMovementAmount($operatingGodown, 'in');
                $operatingOutAmt = $this->sumMovementAmount($operatingGodown, 'out');
                $closingAmt = $openingAmt + $operatingInAmt - $operatingOutAmt;

                $godown = $entries->first()->godown;

                $godownCollection[] = [
                    'godown_id' => $godown->id,
                    'godown_name' => $godown->name,
                    'godown_code' => $godown->code,
                    'opening_quantity' => $openingQty,
                    'opening_amount' => $openingAmt,
                    'inward_quantity' => $operatingIn,
                    'inward_amount' => $operatingInAmt,
                    'outward_quantity' => $operatingOut,
                    'outward_amount' => $operatingOutAmt,
                    'closing_quantity' => $closingQty,
                    'closing_amount' => $closingAmt,
                ];
            }

            // Verify godown totals match item totals
            $calculatedClosing = array_sum(array_column($godownCollection, 'closing_quantity'));
            $calculatedInward = array_sum(array_column($godownCollection, 'inward_quantity'));
            $calculatedOutward = array_sum(array_column($godownCollection, 'outward_quantity'));

            $calculatedClosingAmt = array_sum(array_column($godownCollection, 'closing_amount'));
            $calculatedInwardAmt = array_sum(array_column($godownCollection, 'inward_amount'));
            $calculatedOutwardAmt = array_sum(array_column($godownCollection, 'outward_amount'));

            if (
                abs($calculatedClosing - $itemClosing) > 0.001 ||
                abs($calculatedInward - $itemOperatingIn) > 0.001 ||
                abs($calculatedOutward - $itemOperatingOut) > 0.001
            ) {
                $godownCollection[] = [
                    'godown_id' => null,
                    'godown_name' => 'Mismatch in total',
                    'godown_code' => null,
                    'opening_quantity' => $itemOpening - array_sum(array_column($godownCollection, 'opening_quantity')),
                    'opening_amount' => $itemOpeningAmount - array_sum(array_column($godownCollection, 'opening_amount')),
                    'inward_quantity' => $itemOperatingIn - $calculatedInward,
                    'inward_amount' => $itemOperatingInAmount - $calculatedInwardAmt,
                    'outward_quantity' => $itemOperatingOut - $calculatedOutward,
                    'outward_amount' => $itemOperatingOutAmount - $calculatedOutwardAmt,
                    'closing_quantity' => $itemClosing - $calculatedClosing,
                    'closing_amount' => $itemClosingAmount - $calculatedClosingAmt,
                ];
            }

            $result[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_code' => $item->stock_unit?->code,
                'unit_name' => $item->stock_unit?->name,
                'no_of_decimal_places' => $item->stock_unit?->no_of_decimal_places,
                'opening_quantity' => $itemOpening,
                'opening_amount' => $itemOpeningAmount,
                'inward_quantity' => $itemOperatingIn,
                'inward_amount' => $itemOperatingInAmount,
                'outward_quantity' => $itemOperatingOut,
                'outward_amount' => $itemOperatingOutAmount,
                'closing_quantity' => $itemClosing,
                'closing_amount' => $itemClosingAmount,
                'godown_details' => $godownCollection,
            ];
        }

        return $result;
    }

    /**
     * Stock In Hand — godown-wise with item breakdown
     */
    public function stock_in_hand_godown_wise(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        // Single flat query using direct DB joins instead of deep Eloquent withWhereHas chains
        $rows = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'v.stock_journal_id', '=', 'sj.id')
            ->join('godowns as g', 'sjge.godown_id', '=', 'g.id')
            ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
            ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
            ->where('v.fiscal_year_id', $fiscalYearId)
            // Only include movements up to the as-of date (reporting period end)
            ->when($asOfDate, fn ($q) => $q->where('v.voucher_date', '<=', $asOfDate))
            // Respect Eloquent's not_purged global scope
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_godown_entry_purges')
                    ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
            })
            ->select([
                'g.id as godown_id',
                'g.name as godown_name',
                'g.code as godown_code',
                'si.id as stock_item_id',
                'si.name as stock_item_name',
                'su.code as unit_code',
                'su.name as unit_name',
                'su.no_of_decimal_places',
                'sjge.actual_quantity',
                'sjge.amount',
                'sjge.movement_type',
                'sj.type as stock_journal_type',
            ])
            ->get();

        // Group by godown
        $godownGroups = $rows->groupBy('godown_id');

        $result = [];

        foreach ($godownGroups as $godownId => $godownRows) {
            $firstRow = $godownRows->first();

            // Group by item within godown
            $itemGroups = $godownRows->groupBy('stock_item_id');

            $itemsCollection = [];
            $godownTotals = [
                'opening_quantity' => 0, 'opening_amount' => 0,
                'inward_quantity' => 0, 'inward_amount' => 0,
                'outward_quantity' => 0, 'outward_amount' => 0,
                'closing_quantity' => 0, 'closing_amount' => 0,
            ];

            foreach ($itemGroups as $stockItemId => $itemRows) {
                $firstItem = $itemRows->first();

                $openingQty = 0;
                $openingAmt = 0;
                $operatingIn = 0;
                $operatingInAmt = 0;
                $operatingOut = 0;
                $operatingOutAmt = 0;

                foreach ($itemRows as $row) {
                    $isOpening = $row->stock_journal_type === 'OPENING';
                    $isIn = $row->movement_type === 'in';

                    if ($isOpening) {
                        $openingQty += (float) $row->actual_quantity;
                        $openingAmt += (float) $row->amount;
                    } elseif ($isIn) {
                        $operatingIn += (float) $row->actual_quantity;
                        $operatingInAmt += (float) $row->amount;
                    } else {
                        $operatingOut += (float) $row->actual_quantity;
                        $operatingOutAmt += (float) $row->amount;
                    }
                }

                $closingQty = $openingQty + $operatingIn - $operatingOut;
                $closingAmt = $openingAmt + $operatingInAmt - $operatingOutAmt;

                $itemsCollection[] = [
                    'item_id' => (int) $stockItemId,
                    'item_name' => $firstItem->stock_item_name,
                    'unit_code' => $firstItem->unit_code,
                    'unit_name' => $firstItem->unit_name,
                    'no_of_decimal_places' => (int) $firstItem->no_of_decimal_places,
                    'opening_quantity' => $openingQty,
                    'opening_amount' => $openingAmt,
                    'inward_quantity' => $operatingIn,
                    'inward_amount' => $operatingInAmt,
                    'outward_quantity' => $operatingOut,
                    'outward_amount' => $operatingOutAmt,
                    'closing_quantity' => $closingQty,
                    'closing_amount' => $closingAmt,
                ];

                $godownTotals['opening_quantity'] += $openingQty;
                $godownTotals['opening_amount'] += $openingAmt;
                $godownTotals['inward_quantity'] += $operatingIn;
                $godownTotals['inward_amount'] += $operatingInAmt;
                $godownTotals['outward_quantity'] += $operatingOut;
                $godownTotals['outward_amount'] += $operatingOutAmt;
                $godownTotals['closing_quantity'] += $closingQty;
                $godownTotals['closing_amount'] += $closingAmt;
            }

            $result[] = [
                'godown_id' => (int) $firstRow->godown_id,
                'godown_name' => $firstRow->godown_name,
                'godown_code' => $firstRow->godown_code,
                'opening_quantity' => $godownTotals['opening_quantity'],
                'opening_amount' => $godownTotals['opening_amount'],
                'inward_quantity' => $godownTotals['inward_quantity'],
                'inward_amount' => $godownTotals['inward_amount'],
                'outward_quantity' => $godownTotals['outward_quantity'],
                'outward_amount' => $godownTotals['outward_amount'],
                'closing_quantity' => $godownTotals['closing_quantity'],
                'closing_amount' => $godownTotals['closing_amount'],
                'item_details' => $itemsCollection,
            ];
        }

        // Sort by godown name for consistent ordering
        usort($result, fn ($a, $b) => strcmp($a['godown_name'], $b['godown_name']));

        return $result;
    }

    /**
     * Stock In Hand — zone-wise with godown and item breakdown
     */
    public function stock_in_hand_zone_wise(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        // Get all zones
        $zones = DB::table('godowns')
            ->where('storage_unit_type', 'zone')
            ->orderBy('name')
            ->get();

        // Single flat query for all godown entries with item info
        $rows = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'v.stock_journal_id', '=', 'sj.id')
            ->join('godowns as g', 'sjge.godown_id', '=', 'g.id')
            ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
            ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
            ->where('v.fiscal_year_id', $fiscalYearId)
            // Only include movements up to the as-of date (reporting period end)
            ->when($asOfDate, fn ($q) => $q->where('v.voucher_date', '<=', $asOfDate))
            // Respect Eloquent's not_purged global scope
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_godown_entry_purges')
                    ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
            })
            ->select([
                'g.id as godown_id',
                'g.name as godown_name',
                'g.code as godown_code',
                'g.parent_id',
                'si.id as stock_item_id',
                'si.name as stock_item_name',
                'su.code as unit_code',
                'su.name as unit_name',
                'su.no_of_decimal_places',
                'sjge.actual_quantity',
                'sjge.amount',
                'sjge.movement_type',
                'sj.type as stock_journal_type',
            ])
            ->get();

        // Group by godown for efficient lookup
        $godownGroups = $rows->groupBy('godown_id');

        $result = [];

        foreach ($zones as $zone) {
            // Filter godowns belonging to this zone by parent_id
            $zoneGodownIds = $godownGroups->filter(function ($group) use ($zone) {
                return $group->first()->parent_id == $zone->id;
            })->keys()->toArray();

            $zoneGodowns = [];
            $zoneTotals = [
                'opening_quantity' => 0, 'opening_amount' => 0,
                'inward_quantity' => 0, 'inward_amount' => 0,
                'outward_quantity' => 0, 'outward_amount' => 0,
                'closing_quantity' => 0, 'closing_amount' => 0,
            ];

            foreach ($zoneGodownIds as $godownId) {
                $godownRows = $godownGroups[$godownId];
                $firstGodown = $godownRows->first();

                // Group by item within godown
                $itemGroups = $godownRows->groupBy('stock_item_id');
                $itemsCollection = [];

                foreach ($itemGroups as $stockItemId => $itemRows) {
                    $firstItem = $itemRows->first();

                    $openingQty = 0;
                    $openingAmt = 0;
                    $operatingIn = 0;
                    $operatingInAmt = 0;
                    $operatingOut = 0;
                    $operatingOutAmt = 0;

                    foreach ($itemRows as $row) {
                        $isOpening = $row->stock_journal_type === 'OPENING';
                        $isIn = $row->movement_type === 'in';

                        if ($isOpening) {
                            $openingQty += (float) $row->actual_quantity;
                            $openingAmt += (float) $row->amount;
                        } elseif ($isIn) {
                            $operatingIn += (float) $row->actual_quantity;
                            $operatingInAmt += (float) $row->amount;
                        } else {
                            $operatingOut += (float) $row->actual_quantity;
                            $operatingOutAmt += (float) $row->amount;
                        }
                    }

                    $closingQty = $openingQty + $operatingIn - $operatingOut;
                    $closingAmt = $openingAmt + $operatingInAmt - $operatingOutAmt;

                    $itemsCollection[] = [
                        'item_id' => (int) $stockItemId,
                        'item_name' => $firstItem->stock_item_name,
                        'unit_code' => $firstItem->unit_code,
                        'unit_name' => $firstItem->unit_name,
                        'no_of_decimal_places' => (int) $firstItem->no_of_decimal_places,
                        'opening_quantity' => $openingQty,
                        'opening_amount' => $openingAmt,
                        'inward_quantity' => $operatingIn,
                        'inward_amount' => $operatingInAmt,
                        'outward_quantity' => $operatingOut,
                        'outward_amount' => $operatingOutAmt,
                        'closing_quantity' => $closingQty,
                        'closing_amount' => $closingAmt,
                    ];

                    $zoneTotals['opening_quantity'] += $openingQty;
                    $zoneTotals['opening_amount'] += $openingAmt;
                    $zoneTotals['inward_quantity'] += $operatingIn;
                    $zoneTotals['inward_amount'] += $operatingInAmt;
                    $zoneTotals['outward_quantity'] += $operatingOut;
                    $zoneTotals['outward_amount'] += $operatingOutAmt;
                    $zoneTotals['closing_quantity'] += $closingQty;
                    $zoneTotals['closing_amount'] += $closingAmt;
                }

                $zoneGodowns[] = [
                    'godown_id' => (int) $firstGodown->godown_id,
                    'godown_name' => $firstGodown->godown_name,
                    'godown_code' => $firstGodown->godown_code,
                    'opening_quantity' => array_sum(array_column($itemsCollection, 'opening_quantity')),
                    'opening_amount' => array_sum(array_column($itemsCollection, 'opening_amount')),
                    'inward_quantity' => array_sum(array_column($itemsCollection, 'inward_quantity')),
                    'inward_amount' => array_sum(array_column($itemsCollection, 'inward_amount')),
                    'outward_quantity' => array_sum(array_column($itemsCollection, 'outward_quantity')),
                    'outward_amount' => array_sum(array_column($itemsCollection, 'outward_amount')),
                    'closing_quantity' => array_sum(array_column($itemsCollection, 'closing_quantity')),
                    'closing_amount' => array_sum(array_column($itemsCollection, 'closing_amount')),
                    'item_details' => $itemsCollection,
                ];
            }

            $result[] = [
                'zone_id' => (int) $zone->id,
                'zone_name' => $zone->name,
                'zone_code' => $zone->code,
                'opening_quantity' => $zoneTotals['opening_quantity'],
                'opening_amount' => $zoneTotals['opening_amount'],
                'inward_quantity' => $zoneTotals['inward_quantity'],
                'inward_amount' => $zoneTotals['inward_amount'],
                'outward_quantity' => $zoneTotals['outward_quantity'],
                'outward_amount' => $zoneTotals['outward_amount'],
                'closing_quantity' => $zoneTotals['closing_quantity'],
                'closing_amount' => $zoneTotals['closing_amount'],
                'godowns' => $zoneGodowns,
            ];
        }

        return $result;
    }

    /**
     * Stock In Hand — voucher-wise with voucher breakdown per item
     */
    public function stock_in_hand_voucher_wise(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        $items = StockItem::with([
            'stock_unit',
            'stock_journal_entries' => function ($query) use ($fiscalYearId, $asOfDate) {
                $query->whereHas('stock_journal.voucher', function ($q) use ($fiscalYearId, $asOfDate) {
                    $q->where('fiscal_year_id', $fiscalYearId)
                        ->when($asOfDate, fn ($query) => $query->where('voucher_date', '<=', $asOfDate));
                })->with([
                    'stock_journal.voucher.voucher_type',
                    'stock_journal_godown_entries',
                ]);
            },
        ])->get();

        $result = [];
        foreach ($items as $item) {
            $allEntries = $item->stock_journal_entries
                ->filter(fn ($e) => $e->stock_journal && $e->stock_journal->voucher);

            // Separate opening vs operating
            [$openingEntries, $operatingEntries] = $this->separateOpeningAndOperating($allEntries);

            // Item-level totals
            $itemOpening = $this->sumMovement($openingEntries, 'in');
            $itemOperatingIn = $this->sumMovement($operatingEntries, 'in');
            $itemOperatingOut = $this->sumMovement($operatingEntries, 'out');
            $itemClosing = $itemOpening + $itemOperatingIn - $itemOperatingOut;

            $itemOpeningAmount = $this->sumMovementAmount($openingEntries, 'in');
            $itemOperatingInAmount = $this->sumMovementAmount($operatingEntries, 'in');
            $itemOperatingOutAmount = $this->sumMovementAmount($operatingEntries, 'out');
            $itemClosingAmount = $itemOpeningAmount + $itemOperatingInAmount - $itemOperatingOutAmount;

            // Voucher-wise breakdown
            $voucherCollection = [];

            // Collect all vouchers grouped by voucher, then sort
            $allByVoucher = $allEntries
                ->groupBy(fn ($e) => $e->stock_journal->voucher->id);

            // Sort by voucher type, date, no (same ordering as old code)
            $allByVoucher = $allByVoucher->sortBy(function ($entries) {
                $voucher = $entries->first()->stock_journal->voucher;

                return sprintf(
                    '%03d-%s-%s',
                    $voucher->voucher_type_id,
                    $voucher->voucher_date,
                    $voucher->voucher_no
                );
            });

            foreach ($allByVoucher as $voucherId => $entries) {
                [$vOpening, $vOperating] = $this->separateOpeningAndOperating($entries);

                $voucher = $entries->first()->stock_journal->voucher;
                $openingQty = $this->sumMovement($vOpening, 'in');
                $operatingIn = $this->sumMovement($vOperating, 'in');
                $operatingOut = $this->sumMovement($vOperating, 'out');
                $net = $openingQty + $operatingIn - $operatingOut;

                $openingAmt = $this->sumMovementAmount($vOpening, 'in');
                $operatingInAmt = $this->sumMovementAmount($vOperating, 'in');
                $operatingOutAmt = $this->sumMovementAmount($vOperating, 'out');
                $netAmt = $openingAmt + $operatingInAmt - $operatingOutAmt;

                // Batch / serial / godown-level detail lines for this voucher
                // (e.g. SKADJ physical-count adjustments) so the report can show
                // exactly which batch or serial number was moved.
                $lineDetails = $entries
                    ->flatMap(fn ($e) => $e->stock_journal_godown_entries->map(fn ($ge) => [
                        'stock_item_id' => $e->stock_item_id,
                        'batch_no' => $ge->batch_no,
                        'serial_no' => $ge->serial_no,
                        'mfg_date' => $ge->mfg_date?->format('Y-m-d'),
                        'expiry_date' => $ge->expiry_date?->format('Y-m-d'),
                        'movement_type' => $ge->movement_type,
                        'quantity' => (float) $ge->actual_quantity,
                        'rate' => (float) $ge->rate,
                        'amount' => (float) $ge->amount,
                        'remarks' => $ge->remarks,
                    ]))
                    ->values()
                    ->toArray();

                $voucherCollection[] = [
                    'voucher_id' => $voucher->id,
                    'voucher_type' => $voucher->voucher_type->name,
                    'stock_journal_type' => $entries->first()->stock_journal?->type ?? null,
                    'voucher_no' => $voucher->voucher_no,
                    'voucher_date' => $voucher->voucher_date,
                    'opening_quantity' => $openingQty,
                    'opening_amount' => $openingAmt,
                    'inward_quantity' => $operatingIn,
                    'inward_amount' => $operatingInAmt,
                    'outward_quantity' => $operatingOut,
                    'outward_amount' => $operatingOutAmt,
                    'closing_quantity' => $net,
                    'closing_amount' => $netAmt,
                    'line_details' => $lineDetails,
                ];
            }

            $result[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_code' => $item->stock_unit?->code,
                'unit_name' => $item->stock_unit?->name,
                'no_of_decimal_places' => $item->stock_unit?->no_of_decimal_places,
                'opening_quantity' => $itemOpening,
                'opening_amount' => $itemOpeningAmount,
                'inward_quantity' => $itemOperatingIn,
                'inward_amount' => $itemOperatingInAmount,
                'outward_quantity' => $itemOperatingOut,
                'outward_amount' => $itemOperatingOutAmount,
                'closing_quantity' => $itemClosing,
                'closing_amount' => $itemClosingAmount,
                'voucher_details' => $voucherCollection,
            ];
        }

        return $result;
    }

    /**
     * Get all items with opening/operating/closing summary for the running balance grid.
     */
    public function getRunningBalanceItems(): array
    {
        // Reuse stockInHand() logic which already has opening balance separation
        return $this->stockInHand();
    }

    /**
     * Get godown-level running balance summary.
     */
    public function getRunningBalanceGodowns(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        $godowns = Godown::withWhereHas(
            'stock_journal_godown_entries.stock_journal_entry.stock_journal.voucher',
            fn ($q) => $q->where('fiscal_year_id', $fiscalYearId)->whereHas('stock_journal')
        )
            ->with([
                'stock_journal_godown_entries' => fn ($q) => $q->whereHas(
                    'stock_journal_entry.stock_journal.voucher',
                    fn ($v) => $v->where('fiscal_year_id', $fiscalYearId)->whereHas('stock_journal')
                ),
            ])
            ->get();

        $result = [];
        foreach ($godowns as $godown) {
            $entries = $godown->stock_journal_godown_entries;
            [$openingEntries, $operatingEntries] = $this->separateOpeningAndOperatingGodown($entries);

            $opening = $this->sumMovement($openingEntries, 'in');
            $inward = $this->sumMovement($operatingEntries, 'in');
            $outward = $this->sumMovement($operatingEntries, 'out');
            $closing = $opening + $inward - $outward;

            $result[] = [
                'godown_id' => $godown->id,
                'godown_name' => $godown->name,
                'godown_code' => $godown->code,
                'opening_quantity' => $opening,
                'inward_quantity' => $inward,
                'outward_quantity' => $outward,
                'closing_quantity' => $closing,
            ];
        }

        return $result;
    }

    /**
     * Get items within a specific godown with their running balance quantities.
     */
    public function getGodownRunningBalanceItems(int $godownId): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        $godown = Godown::with([
            'stock_journal_godown_entries' => fn ($q) => $q->whereHas(
                'stock_journal_entry.stock_journal.voucher',
                fn ($v) => $v->where('fiscal_year_id', $fiscalYearId)->whereHas('stock_journal')
            )->with([
                'stock_journal_entry.stock_item.stock_unit',
                'stock_journal_entry.stock_journal.voucher',
            ]),
        ])->findOrFail($godownId);

        $grouped = $godown->stock_journal_godown_entries
            ->groupBy(fn ($e) => $e->stock_journal_entry->stock_item_id);

        $items = [];
        foreach ($grouped as $itemId => $entries) {
            [$openingEntries, $operatingEntries] = $this->separateOpeningAndOperatingGodown($entries);

            $opening = $this->sumMovement($openingEntries, 'in');
            $inward = $this->sumMovement($operatingEntries, 'in');
            $outward = $this->sumMovement($operatingEntries, 'out');
            $closing = $opening + $inward - $outward;

            $item = $entries->first()->stock_journal_entry->stock_item;

            $items[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_code' => $item->stock_unit?->code,
                'unit_name' => $item->stock_unit?->name,
                'no_of_decimal_places' => $item->stock_unit?->no_of_decimal_places,
                'opening_quantity' => $opening,
                'inward_quantity' => $inward,
                'outward_quantity' => $outward,
                'closing_quantity' => $closing,
            ];
        }

        return [
            'godown' => [
                'godown_id' => $godown->id,
                'godown_name' => $godown->name,
                'godown_code' => $godown->code,
            ],
            'items' => $items,
        ];
    }

    /**
     * Get detailed running balance for a single item with chronological transactions
     * and a running balance (cumulative) column. Optionally filter by godown.
     */
    public function getRunningBalance(int $itemId, ?int $godownId = null): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        $item = StockItem::with([
            'stock_unit',
            'stock_journal_entries' => function ($query) use ($fiscalYearId, $godownId) {
                $query->whereHas('stock_journal.voucher', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId);
                });

                if ($godownId !== null) {
                    $query->whereHas('stock_journal_godown_entries', fn ($q) => $q->where('godown_id', $godownId));
                }

                $query->with([
                    'stock_journal.voucher.voucher_type',
                    'stock_journal_godown_entries' => function ($q) use ($godownId) {
                        if ($godownId !== null) {
                            $q->where('godown_id', $godownId);
                        }
                        $q->with('godown');
                    },
                ]);
            },
        ])->findOrFail($itemId);

        $allEntries = $item->stock_journal_entries
            ->filter(fn ($e) => $e->stock_journal && $e->stock_journal->voucher);

        // Separate opening vs operating
        [$openingEntries, $operatingEntries] = $this->separateOpeningAndOperating($allEntries);

        // Calculate opening balance — when godown-filtered, use godown entry quantities directly
        $openingQuantity = 0;
        if ($godownId !== null) {
            foreach ($openingEntries as $entry) {
                foreach ($entry->stock_journal_godown_entries as $ge) {
                    if ((float) $ge->actual_quantity > 0) {
                        $openingQuantity += (float) $ge->actual_quantity;
                    }
                }
            }
        } else {
            $openingQuantity = $this->sumMovement($openingEntries, 'in');
        }

        // Gather all transactions chronologically
        $transactions = [];

        // Add opening as first transaction
        if ($openingQuantity > 0) {
            $openingVoucher = $openingEntries->first()?->stock_journal?->voucher;
            $transactions[] = [
                'voucher_id' => $openingVoucher?->id,
                'voucher_type' => $openingVoucher?->voucher_type?->name ?? 'Opening',
                'voucher_no' => $openingVoucher?->voucher_no ?? 'OPENING',
                'voucher_date' => $openingVoucher?->voucher_date,
                'inward_quantity' => $openingQuantity,
                'outward_quantity' => 0,
                'running_balance' => $openingQuantity,
                'is_opening' => true,
            ];
        }

        // Group operating entries by voucher, sorted chronologically
        $operatingByVoucher = $operatingEntries
            ->groupBy(fn ($e) => $e->stock_journal->voucher->id)
            ->sortBy(function ($entries) {
                $v = $entries->first()->stock_journal->voucher;

                return ($v->voucher_date?->format('Y-m-d') ?? $v->voucher_date).'|'.$v->voucher_no;
            });

        $runningBalance = $openingQuantity;

        foreach ($operatingByVoucher as $voucherId => $entries) {
            $voucher = $entries->first()->stock_journal->voucher;

            // Calculate inward/outward from godown entries when filtered, otherwise from journal entries
            $inward = 0;
            $outward = 0;
            if ($godownId !== null) {
                foreach ($entries as $entry) {
                    foreach ($entry->stock_journal_godown_entries as $ge) {
                        $qty = (float) $ge->actual_quantity;
                        if ($ge->movement_type === 'in') {
                            $inward += $qty;
                        } elseif ($ge->movement_type === 'out') {
                            $outward += $qty;
                        }
                    }
                }
            } else {
                $inward = $this->sumMovement($entries, 'in');
                $outward = $this->sumMovement($entries, 'out');
            }

            $runningBalance += $inward - $outward;

            // Get godown details for this transaction — one aggregated row per godown,
            // plus per-godown batch/serial detail lines so the report shows exactly
            // which batch or serial number moved (e.g. SKADJ physical-count adjustments).
            $godownDetails = $entries->flatMap(fn ($e) => $e->stock_journal_godown_entries)
                ->groupBy('godown_id')
                ->map(function ($geEntries) {
                    $ge = $geEntries->first();
                    $qtyIn = $this->sumMovement($geEntries, 'in');
                    $qtyOut = $this->sumMovement($geEntries, 'out');

                    $detailLines = $geEntries->map(fn ($ge) => [
                        'batchNo' => $ge->batch_no,
                        'serialNo' => $ge->serial_no,
                        'mfgDate' => $ge->mfg_date?->format('Y-m-d'),
                        'expiryDate' => $ge->expiry_date?->format('Y-m-d'),
                        'movementType' => $ge->movement_type,
                        'quantity' => (float) $ge->actual_quantity,
                        'rate' => (float) $ge->rate,
                        'amount' => (float) $ge->amount,
                        'remarks' => $ge->remarks,
                    ])->values()->toArray();

                    return [
                        'godown_id' => $ge->godown_id,
                        'godown_name' => $ge->godown->name ?? null,
                        'inward_quantity' => $qtyIn,
                        'outward_quantity' => $qtyOut,
                        'net_quantity' => $qtyIn - $qtyOut,
                        'detailLines' => $detailLines,
                    ];
                })
                ->values()
                ->toArray();

            $transactions[] = [
                'voucher_id' => $voucher->id,
                'voucher_type' => $voucher->voucher_type->name,
                'voucher_no' => $voucher->voucher_no,
                'voucher_date' => $voucher->voucher_date,
                'inward_quantity' => $inward,
                'outward_quantity' => $outward,
                'running_balance' => $runningBalance,
                'is_opening' => false,
                'godown_details' => $godownDetails,
            ];
        }

        // Calculate totals
        $totalInward = array_sum(array_column($transactions, 'inward_quantity'));
        $totalOutward = array_sum(array_column($transactions, 'outward_quantity'));

        return [
            'item' => [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'unit_code' => $item->stock_unit?->code,
                'unit_name' => $item->stock_unit?->name,
                'no_of_decimal_places' => $item->stock_unit?->no_of_decimal_places ?? 2,
            ],
            'opening_quantity' => $openingQuantity,
            'total_inward' => $totalInward,
            'total_outward' => $totalOutward,
            'closing_quantity' => $runningBalance,
            'transactions' => $transactions,
        ];
    }

    // ──────────────────────────────────────────────
    //  Helper Methods
    // ──────────────────────────────────────────────

    /**
     * Separate a collection of StockJournalEntry models into opening vs operating.
     *
     * Opening entries are those whose StockJournal.type === 'OPENING' (created by FiscalYearOpen).
     * Operating entries are everything else (purchases, sales, adjustments, etc.).
     *
     * @param  Collection  $entries  Collection of StockJournalEntry models
     * @return array [openingEntries, operatingEntries]
     */
    protected function separateOpeningAndOperating($entries): array
    {
        $opening = $entries->filter(fn ($e) => $e->stock_journal && $e->stock_journal->type === 'OPENING');
        $operating = $entries->filter(fn ($e) => ! $e->stock_journal || $e->stock_journal->type !== 'OPENING');

        return [$opening, $operating];
    }

    /**
     * Separate a collection of StockJournalGodownEntry models into opening vs operating.
     *
     * @param  Collection  $entries  Collection of StockJournalGodownEntry models
     * @return array [openingEntries, operatingEntries]
     */
    protected function separateOpeningAndOperatingGodown($entries): array
    {
        $opening = $entries->filter(fn ($e) => $e->stock_journal_entry
            && $e->stock_journal_entry->stock_journal
            && $e->stock_journal_entry->stock_journal->type === 'OPENING');

        $operating = $entries->filter(fn ($e) => ! $e->stock_journal_entry
            || ! $e->stock_journal_entry->stock_journal
            || $e->stock_journal_entry->stock_journal->type !== 'OPENING');

        return [$opening, $operating];
    }

    /**
     * Sum actual_quantity for entries matching the given movement_type.
     */
    protected function sumMovement($entries, string $movementType): float
    {
        return (float) $entries
            ->where('movement_type', $movementType)
            ->sum('actual_quantity');
    }

    /**
     * Sum amount for entries matching the given movement_type.
     */
    protected function sumMovementAmount($entries, string $movementType): float
    {
        return (float) $entries
            ->where('movement_type', $movementType)
            ->sum('amount');
    }

    /**
     * Calculate item-level totals with opening balance separation
     *
     * @param  Collection  $stockJournalEntries  Collection of StockJournalEntry
     * @return array ['opening' => float, 'operating_in' => float, 'operating_out' => float, 'closing' => float, ...amount fields]
     */
    protected function calculateItemOpeningAndOperating($stockJournalEntries): array
    {
        [$openingEntries, $operatingEntries] = $this->separateOpeningAndOperating($stockJournalEntries);

        $opening = $this->sumMovement($openingEntries, 'in');
        $operatingIn = $this->sumMovement($operatingEntries, 'in');
        $operatingOut = $this->sumMovement($operatingEntries, 'out');
        $closing = $opening + $operatingIn - $operatingOut;

        $openingAmount = $this->sumMovementAmount($openingEntries, 'in');
        $operatingInAmount = $this->sumMovementAmount($operatingEntries, 'in');
        $operatingOutAmount = $this->sumMovementAmount($operatingEntries, 'out');
        $closingAmount = $openingAmount + $operatingInAmount - $operatingOutAmount;

        return [
            'opening' => $opening,
            'operating_in' => $operatingIn,
            'operating_out' => $operatingOut,
            'closing' => $closing,
            'opening_amount' => $openingAmount,
            'operating_in_amount' => $operatingInAmount,
            'operating_out_amount' => $operatingOutAmount,
            'closing_amount' => $closingAmount,
        ];
    }

    // ──────────────────────────────────────────────
    //  Stub methods (to be implemented as needed)
    // ──────────────────────────────────────────────

    public function netStock(array $data): StockSummary
    {
        return StockSummary::first();
    }

    public function purchaseOrderOutstanding(): StockSummary
    {
        return StockSummary::first();
    }

    public function salebleStock(): StockSummary
    {
        return StockSummary::first();
    }

    public function salesOrderOutstanding(): StockSummary
    {
        return StockSummary::first();
    }
}
