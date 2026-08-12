<?php

namespace Modules\StockSummary\Services;

use App\Enums\MovementType;
use App\Support\Services\BaseService;
use App\Support\Traits\HasItemAverageRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
     * The reporting-period start date — the user's reporting-period start when
     * set, otherwise the fiscal year start (full-year view).
     */
    protected function getPeriodStart(?FiscalYear $fiscalYear = null): ?Carbon
    {
        $fiscalYear ??= FiscalYear::find($this->userFiscalYear?->fiscal_year_id);

        $periodStart = $this->userFiscalYear?->start_date ?? $fiscalYear?->start_date;

        return $periodStart ? Carbon::parse($periodStart) : null;
    }

    /**
     * Whether the reporting period starts on the fiscal year's first date.
     *
     * When true (or when no reporting period is set) the opening balance of the
     * stock-in-hand reports is the 9010 / OPENING opening-stock entries only.
     * When false (mid-year period) the opening balance is recalculated as the
     * stock position at the start of the reporting period, including 9010.
     */
    protected function startsAtFiscalYearStart(?Carbon $periodStart, ?Carbon $fyStart): bool
    {
        if (! $periodStart) {
            return true; // no reporting period → full fiscal-year view
        }

        if (! $fyStart) {
            return true;
        }

        return $periodStart->equalTo($fyStart);
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
     * Base query for the stock-in-hand report: stock items that have fiscal-year
     * movements, with the same nested eager loads the report uses. The StockItem
     * `orderByName` global scope is kept so per-chunk iteration preserves the
     * by-name ordering callers already see.
     */
    protected function stockInHandQuery(): Builder
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        return StockItem::withWhereHas(
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
                            'stock_journal.voucher',
                        ]);
                },
            ])
            // id tiebreaker keeps chunk() pagination stable when names collide
            // (ORDER BY name, id — orderByName scope first, then id).
            ->orderBy('id');
    }

    /**
     * Base query for the item-wise stock-in-hand report (all items, with godown
     * breakdown). Kept ordered by name so per-chunk iteration preserves order.
     */
    protected function stockInHandItemWiseQuery(): Builder
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        return StockItem::with([
            'stock_unit',
            'stock_journal_entries' => function ($query) use ($fiscalYearId, $asOfDate) {
                $query->whereHas('stock_journal.voucher', function ($q) use ($fiscalYearId, $asOfDate) {
                    $q->where('fiscal_year_id', $fiscalYearId)
                        ->when($asOfDate, fn ($query) => $query->where('voucher_date', '<=', $asOfDate));
                })
                    ->with([
                        'stock_journal_godown_entries.godown',
                        'stock_journal.voucher',
                    ]);
            },
        ])
            // id tiebreaker keeps chunk() pagination stable when names collide.
            ->orderBy('id');
    }

    /**
     * Base query for the voucher-wise stock-in-hand report (all items, with
     * voucher and godown detail lines). Kept ordered by name.
     */
    protected function stockInHandVoucherWiseQuery(): Builder
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();

        return StockItem::with([
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
        ])
            // id tiebreaker keeps chunk() pagination stable when names collide.
            ->orderBy('id');
    }

    /**
     * Base query for the godown running-balance report: godowns with fiscal-year
     * godown entries, ordered by name.
     */
    protected function runningBalanceGodownsQuery(): Builder
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        return Godown::withWhereHas(
            'stock_journal_godown_entries.stock_journal_entry.stock_journal.voucher',
            fn ($q) => $q->where('fiscal_year_id', $fiscalYearId)->whereHas('stock_journal')
        )
            ->with([
                'stock_journal_godown_entries' => fn ($q) => $q->whereHas(
                    'stock_journal_entry.stock_journal.voucher',
                    fn ($v) => $v->where('fiscal_year_id', $fiscalYearId)->whereHas('stock_journal')
                ),
            ])
            // id tiebreaker keeps chunk() pagination stable when names collide.
            ->orderBy('id');
    }

    /**
     * Flat query over stock-journal godown entries for the godown/zone-wise
     * stock-in-hand reports (direct joins, not_purged-aware). Rows are consumed
     * in chunks so peak memory stays bounded regardless of movement volume.
     */
    protected function stockInHandGodownRowsQuery(int $fiscalYearId, ?Carbon $asOfDate, bool $includeParentId = false): QueryBuilder
    {
        // sjge.id is selected so chunkById can paginate on it (see the report methods).
        $columns = [
            'sjge.id',
            'g.id as godown_id',
            'g.name as godown_name',
            'g.code as godown_code',
            'v.voucher_date',
            'si.id as stock_item_id',
            'si.name as stock_item_name',
            'su.code as unit_code',
            'su.name as unit_name',
            'su.no_of_decimal_places',
            'sjge.actual_quantity',
            'sjge.amount',
            'sjge.movement_type',
            'sj.type as stock_journal_type',
        ];

        if ($includeParentId) {
            $columns[] = 'g.parent_id';
        }

        return DB::table('stock_journal_godown_entries as sjge')
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
            // Deterministic order — chunk() requires an orderBy and id-based
            // pagination keeps offsets stable.
            ->orderBy('sjge.id')
            ->select($columns);
    }

    /**
     * Accumulate one chunk of flat movement rows into per-godown → per-item
     * aggregate counters (opening/operating in/out), so raw rows never stay in
     * memory beyond their chunk.
     */
    protected function fillGodownItemBuckets(array &$godownBuckets, iterable $rows, ?Carbon $periodStart, bool $startsAtFyStart, bool $captureParent = false): void
    {
        foreach ($rows as $row) {
            $godownId = (int) $row->godown_id;
            $itemId = (int) $row->stock_item_id;

            $bucket = &$godownBuckets[$godownId];
            if (! isset($bucket)) {
                $bucket = [
                    'godown_id' => $godownId,
                    'godown_name' => $row->godown_name,
                    'godown_code' => $row->godown_code,
                    'items' => [],
                ];
                if ($captureParent) {
                    $bucket['parent_id'] = $row->parent_id;
                }
            }

            $item = &$bucket['items'][$itemId];
            if (! isset($item)) {
                $item = [
                    'item_id' => $itemId,
                    'item_name' => $row->stock_item_name,
                    'unit_code' => $row->unit_code,
                    'unit_name' => $row->unit_name,
                    // (int) null → 0 when the item has no stock unit — same as the
                    // pre-chunk implementation, which emitted (int) $row->no_of_decimal_places.
                    'no_of_decimal_places' => (int) $row->no_of_decimal_places,
                    'opening_qty' => 0,
                    'opening_amt' => 0,
                    'in_qty' => 0,
                    'in_amt' => 0,
                    'out_qty' => 0,
                    'out_amt' => 0,
                ];
            }

            $isOpening = $this->isOpeningRow($row, $periodStart, $startsAtFyStart);
            $isIn = $row->movement_type === 'in';
            $qty = (float) $row->actual_quantity;
            $amt = (float) $row->amount;

            if ($isOpening) {
                if ($isIn) {
                    $item['opening_qty'] += $qty;
                    $item['opening_amt'] += $amt;
                } else {
                    $item['opening_qty'] -= $qty;
                    $item['opening_amt'] -= $amt;
                }
            } elseif ($isIn) {
                $item['in_qty'] += $qty;
                $item['in_amt'] += $amt;
            } else {
                $item['out_qty'] += $qty;
                $item['out_amt'] += $amt;
            }

            unset($item, $bucket);
        }
    }

    /**
     * Finalize a godown bucket into per-item report rows plus godown totals.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, float|int>} [items, totals]
     */
    protected function finalizeGodownBucketItems(array $bucket): array
    {
        $itemsCollection = [];
        $totals = [
            'opening_quantity' => 0,
            'opening_amount' => 0,
            'inward_quantity' => 0,
            'inward_amount' => 0,
            'outward_quantity' => 0,
            'outward_amount' => 0,
            'closing_quantity' => 0,
            'closing_amount' => 0,
        ];

        foreach ($bucket['items'] as $item) {
            $closingQty = $item['opening_qty'] + $item['in_qty'] - $item['out_qty'];
            $closingAmt = $item['opening_amt'] + $item['in_amt'] - $item['out_amt'];

            $itemsCollection[] = [
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'unit_code' => $item['unit_code'],
                'unit_name' => $item['unit_name'],
                'no_of_decimal_places' => $item['no_of_decimal_places'],
                'opening_quantity' => $item['opening_qty'],
                'opening_amount' => $item['opening_amt'],
                'inward_quantity' => $item['in_qty'],
                'inward_amount' => $item['in_amt'],
                'outward_quantity' => $item['out_qty'],
                'outward_amount' => $item['out_amt'],
                'closing_quantity' => $closingQty,
                'closing_amount' => $closingAmt,
            ];

            $totals['opening_quantity'] += $item['opening_qty'];
            $totals['opening_amount'] += $item['opening_amt'];
            $totals['inward_quantity'] += $item['in_qty'];
            $totals['inward_amount'] += $item['in_amt'];
            $totals['outward_quantity'] += $item['out_qty'];
            $totals['outward_amount'] += $item['out_amt'];
            $totals['closing_quantity'] += $closingQty;
            $totals['closing_amount'] += $closingAmt;
        }

        return [$itemsCollection, $totals];
    }

    /**
     * Stock In Hand — item-level summary
     * Formula: Closing Quantity = Opening Quantity + Operating Inward - Operating Outward
     */
    public function stockInHand(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $periodStart = $this->getPeriodStart($fiscalYear);
        $fyStart = $fiscalYear?->start_date ? Carbon::parse($fiscalYear->start_date) : null;
        $startsAtFyStart = $this->startsAtFiscalYearStart($periodStart, $fyStart);

        $result = [];

        // Offset-based chunk() (not chunkById) keeps the StockItem `orderByName`
        // global scope, so per-chunk iteration preserves the by-name ordering
        // callers already see — and only one batch of models is in memory at a
        // time (previously the full item + movement tree was hydrated).
        $this->stockInHandQuery()->chunk(200, function ($items) use (&$result, $periodStart, $startsAtFyStart) {
            foreach ($items as $item) {
                $stock = $this->calculateItemOpeningAndOperating(
                    $item->stock_journal_entries,
                    $periodStart,
                    $startsAtFyStart,
                );

                $result[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_code' => $item->stock_unit ? $item->stock_unit->code : null,
                    'unit_name' => $item->stock_unit ? $item->stock_unit->name : null,
                    'no_of_decimal_places' => $item->stock_unit ? $item->stock_unit->no_of_decimal_places : null,
                    'opening_quantity' => $stock['opening'],
                    'opening_amount' => $stock['opening_amount'],
                    'inward_quantity' => $stock['operating_in'],
                    'inward_amount' => $stock['operating_in_amount'],
                    'outward_quantity' => $stock['operating_out'],
                    'outward_amount' => $stock['operating_out_amount'],
                    'closing_quantity' => $stock['closing'],
                    'closing_amount' => $stock['closing_amount'],
                ];
            }
        });

        return $result;
    }

    /**
     * Stock In Hand — item-wise with godown breakdown
     */
    public function stock_in_hand_item_wise(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $periodStart = $this->getPeriodStart($fiscalYear);
        $fyStart = $fiscalYear?->start_date ? Carbon::parse($fiscalYear->start_date) : null;
        $startsAtFyStart = $this->startsAtFiscalYearStart($periodStart, $fyStart);

        $result = [];

        // Stream items in chunks so only one batch of Eloquent models is in
        // memory at a time.
        $this->stockInHandItemWiseQuery()->chunk(200, function ($items) use (&$result, $periodStart, $startsAtFyStart) {
            foreach ($items as $item) {
                $allEntries = $item->stock_journal_entries;

                // Separate opening vs operating entries
                [$openingEntries, $operatingEntries] = $this->splitOpeningAndOperating($allEntries, $periodStart, $startsAtFyStart);

                // Item-level totals
                $itemOpening = $this->sumMovement($openingEntries, 'in') - $this->sumMovement($openingEntries, 'out');
                $itemOperatingIn = $this->sumMovement($operatingEntries, 'in');
                $itemOperatingOut = $this->sumMovement($operatingEntries, 'out');
                $itemClosing = $itemOpening + $itemOperatingIn - $itemOperatingOut;

                $itemOpeningAmount = $this->sumMovementAmount($openingEntries, 'in') - $this->sumMovementAmount($openingEntries, 'out');
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
                    [$openingGodown, $operatingGodown] = $this->splitOpeningOperatingGodownEntries($entries, $periodStart, $startsAtFyStart);

                    $openingQty = $this->sumMovement($openingGodown, 'in') - $this->sumMovement($openingGodown, 'out');
                    $operatingIn = $this->sumMovement($operatingGodown, 'in');
                    $operatingOut = $this->sumMovement($operatingGodown, 'out');
                    $closingQty = $openingQty + $operatingIn - $operatingOut;

                    $openingAmt = $this->sumMovementAmount($openingGodown, 'in') - $this->sumMovementAmount($openingGodown, 'out');
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
        });

        return $result;
    }

    /**
     * Stock In Hand — godown-wise with item breakdown
     */
    public function stock_in_hand_godown_wise(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $asOfDate = $this->getAsOfDate();
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $periodStart = $this->getPeriodStart($fiscalYear);
        $fyStart = $fiscalYear?->start_date ? Carbon::parse($fiscalYear->start_date) : null;
        $startsAtFyStart = $this->startsAtFiscalYearStart($periodStart, $fyStart);

        // Per-godown → per-item aggregate buckets, filled incrementally so only
        // one chunk of raw rows is in memory at a time (the previous flat
        // ->get() held every movement row for the whole period at once).
        $godownBuckets = [];

        // chunkById paginates on sjge.id — buckets are order-independent aggregates,
        // so id-based pagination is both deterministic and offset-free.
        $this->stockInHandGodownRowsQuery($fiscalYearId, $asOfDate)
            ->chunkById(200, function ($rows) use (&$godownBuckets, $periodStart, $startsAtFyStart) {
                $this->fillGodownItemBuckets($godownBuckets, $rows, $periodStart, $startsAtFyStart);
            }, 'sjge.id', 'id');

        $result = [];

        foreach ($godownBuckets as $bucket) {
            [$itemsCollection, $godownTotals] = $this->finalizeGodownBucketItems($bucket);

            $result[] = [
                'godown_id' => $bucket['godown_id'],
                'godown_name' => $bucket['godown_name'],
                'godown_code' => $bucket['godown_code'],
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
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $periodStart = $this->getPeriodStart($fiscalYear);
        $fyStart = $fiscalYear?->start_date ? Carbon::parse($fiscalYear->start_date) : null;
        $startsAtFyStart = $this->startsAtFiscalYearStart($periodStart, $fyStart);

        // Get all zones
        $zones = DB::table('godowns')
            ->where('storage_unit_type', 'zone')
            ->orderBy('name')
            ->get();

        // Per-godown → per-item aggregate buckets, filled incrementally so only
        // one chunk of raw rows is in memory at a time.
        $godownBuckets = [];

        // chunkById paginates on sjge.id — buckets are order-independent aggregates,
        // so id-based pagination is both deterministic and offset-free.
        $this->stockInHandGodownRowsQuery($fiscalYearId, $asOfDate, includeParentId: true)
            ->chunkById(200, function ($rows) use (&$godownBuckets, $periodStart, $startsAtFyStart) {
                $this->fillGodownItemBuckets($godownBuckets, $rows, $periodStart, $startsAtFyStart, captureParent: true);
            }, 'sjge.id', 'id');

        $result = [];

        foreach ($zones as $zone) {
            $zoneGodowns = [];
            $zoneTotals = [
                'opening_quantity' => 0, 'opening_amount' => 0,
                'inward_quantity' => 0, 'inward_amount' => 0,
                'outward_quantity' => 0, 'outward_amount' => 0,
                'closing_quantity' => 0, 'closing_amount' => 0,
            ];

            foreach ($godownBuckets as $bucket) {
                // Filter godowns belonging to this zone by parent_id
                if ((int) $bucket['parent_id'] !== (int) $zone->id) {
                    continue;
                }

                [$itemsCollection, $godownTotals] = $this->finalizeGodownBucketItems($bucket);

                foreach ($godownTotals as $key => $value) {
                    $zoneTotals[$key] += $value;
                }

                $zoneGodowns[] = [
                    'godown_id' => $bucket['godown_id'],
                    'godown_name' => $bucket['godown_name'],
                    'godown_code' => $bucket['godown_code'],
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
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $periodStart = $this->getPeriodStart($fiscalYear);
        $fyStart = $fiscalYear?->start_date ? Carbon::parse($fiscalYear->start_date) : null;
        $startsAtFyStart = $this->startsAtFiscalYearStart($periodStart, $fyStart);

        $result = [];

        // Stream items in chunks so only one batch of Eloquent models is in
        // memory at a time.
        $this->stockInHandVoucherWiseQuery()->chunk(200, function ($items) use (&$result, $periodStart, $startsAtFyStart) {
            foreach ($items as $item) {
                $allEntries = $item->stock_journal_entries
                    ->filter(fn ($e) => $e->stock_journal && $e->stock_journal->voucher);

                // Separate opening vs operating
                [$openingEntries, $operatingEntries] = $this->splitOpeningAndOperating($allEntries, $periodStart, $startsAtFyStart);

                // Item-level totals
                $itemOpening = $this->sumMovement($openingEntries, 'in') - $this->sumMovement($openingEntries, 'out');
                $itemOperatingIn = $this->sumMovement($operatingEntries, 'in');
                $itemOperatingOut = $this->sumMovement($operatingEntries, 'out');
                $itemClosing = $itemOpening + $itemOperatingIn - $itemOperatingOut;

                $itemOpeningAmount = $this->sumMovementAmount($openingEntries, 'in') - $this->sumMovementAmount($openingEntries, 'out');
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
                    [$vOpening, $vOperating] = $this->splitOpeningAndOperating($entries, $periodStart, $startsAtFyStart);

                    $voucher = $entries->first()->stock_journal->voucher;
                    $openingQty = $this->sumMovement($vOpening, 'in') - $this->sumMovement($vOpening, 'out');
                    $operatingIn = $this->sumMovement($vOperating, 'in');
                    $operatingOut = $this->sumMovement($vOperating, 'out');
                    $net = $openingQty + $operatingIn - $operatingOut;

                    $openingAmt = $this->sumMovementAmount($vOpening, 'in') - $this->sumMovementAmount($vOpening, 'out');
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
        });

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

        $result = [];

        // Stream godowns in chunks so peak memory stays bounded.
        $this->runningBalanceGodownsQuery()->chunk(200, function ($godowns) use (&$result) {
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
        });

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
     * Whether a stock journal type represents an opening-stock entry.
     *
     * Two distinct producers write opening stock into stock journals:
     *   - 'OPENING' — the unified opening entry created by FiscalYearOpen.
     *   - 'OPNSK'   — the legacy Opening Stock (OPNSK) voucher flow.
     *
     * Both must be classified as opening in the stock reports; anything else
     * (purchases, sales, conversions, adjustments, ...) is operating.
     */
    protected function isOpeningType(?string $type): bool
    {
        return in_array($type, ['OPENING', 'OPNSK'], true);
    }

    /**
     * Separate a collection of StockJournalEntry models into opening vs operating.
     *
     * Opening entries are those whose StockJournal.type is an opening type
     * ('OPENING' created by FiscalYearOpen, or 'OPNSK' opening-stock vouchers).
     * Operating entries are everything else (purchases, sales, adjustments, etc.).
     *
     * @param  Collection  $entries  Collection of StockJournalEntry models
     * @return array [openingEntries, operatingEntries]
     */
    protected function separateOpeningAndOperating($entries): array
    {
        $opening = $entries->filter(fn ($e) => $e->stock_journal && $this->isOpeningType($e->stock_journal->type));
        $operating = $entries->filter(fn ($e) => ! $e->stock_journal || ! $this->isOpeningType($e->stock_journal->type));

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
            && $this->isOpeningType($e->stock_journal_entry->stock_journal->type));

        $operating = $entries->filter(fn ($e) => ! $e->stock_journal_entry
            || ! $e->stock_journal_entry->stock_journal
            || ! $this->isOpeningType($e->stock_journal_entry->stock_journal->type));

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
     * Split a collection of StockJournalEntry models into opening vs operating
     * for the stock-in-hand reports, honouring the reporting period.
     *
     * - When the reporting period starts on the fiscal year's first day (or no
     *   reporting period is set), opening = the opening-stock entries only
     *   (stock journal type 'OPENING' / 'OPNSK' — the 9010 opening voucher).
     * - When the reporting period starts later, the opening balance is
     *   recalculated as the stock position at the period's start: every
     *   movement dated BEFORE the period start (including the 9010 opening
     *   voucher) is opening, and operating covers movements inside the period.
     *
     * @param  Collection  $entries  Collection of StockJournalEntry models
     * @return array [openingEntries, operatingEntries]
     */
    protected function splitOpeningAndOperating($entries, ?Carbon $periodStart, bool $startsAtFyStart): array
    {
        if ($startsAtFyStart) {
            return $this->separateOpeningAndOperating($entries);
        }

        $isBeforePeriod = function ($entry) use ($periodStart) {
            $date = $this->journalVoucherDate($entry);

            return $date !== null && $periodStart !== null && $date->lt($periodStart);
        };

        $opening = $entries->filter($isBeforePeriod);
        $operating = $entries->reject($isBeforePeriod);

        return [$opening, $operating];
    }

    /**
     * Same as splitOpeningAndOperating() but for a collection of
     * StockJournalGodownEntry models (item-wise godown breakdown).
     *
     * @return array [openingEntries, operatingEntries]
     */
    protected function splitOpeningOperatingGodownEntries($entries, ?Carbon $periodStart, bool $startsAtFyStart): array
    {
        if ($startsAtFyStart) {
            return $this->separateOpeningAndOperatingGodown($entries);
        }

        $isBeforePeriod = function ($godownEntry) use ($periodStart) {
            $date = $this->godownEntryDate($godownEntry);

            return $date !== null && $periodStart !== null && $date->lt($periodStart);
        };

        $opening = $entries->filter($isBeforePeriod);
        $operating = $entries->reject($isBeforePeriod);

        return [$opening, $operating];
    }

    /**
     * Voucher date of a StockJournalEntry (via its stock journal's voucher).
     */
    protected function journalVoucherDate($entry): ?Carbon
    {
        $date = $entry->stock_journal?->voucher?->voucher_date;

        return $date ? Carbon::parse($date) : null;
    }

    /**
     * Voucher date of a StockJournalGodownEntry (via entry → journal → voucher).
     */
    protected function godownEntryDate($godownEntry): ?Carbon
    {
        $date = $godownEntry->stock_journal_entry?->stock_journal?->voucher?->voucher_date;

        return $date ? Carbon::parse($date) : null;
    }

    /**
     * Whether a flat DB row (godown/zone-wise views) is part of the opening
     * balance — type-based at the FY start, date-based for mid-year periods.
     */
    protected function isOpeningRow($row, ?Carbon $periodStart, bool $startsAtFyStart): bool
    {
        if ($startsAtFyStart) {
            return $this->isOpeningType($row->stock_journal_type ?? null);
        }

        $date = ! empty($row->voucher_date) ? Carbon::parse($row->voucher_date) : null;

        return $date !== null && $periodStart !== null && $date->lt($periodStart);
    }

    /**
     * Calculate item-level totals with opening balance separation.
     *
     * Opening is a NET balance: at the FY start it is the opening-stock
     * (9010 / OPENING) entries; for mid-year reporting periods it is the
     * recalculated stock position before the period start (including 9010),
     * which may contain both inward and outward movements.
     *
     * @param  Collection  $stockJournalEntries  Collection of StockJournalEntry
     * @return array ['opening' => float, 'operating_in' => float, 'operating_out' => float, 'closing' => float, ...amount fields]
     */
    protected function calculateItemOpeningAndOperating($stockJournalEntries, ?Carbon $periodStart = null, bool $startsAtFyStart = true): array
    {
        [$openingEntries, $operatingEntries] = $this->splitOpeningAndOperating($stockJournalEntries, $periodStart, $startsAtFyStart);

        $opening = $this->sumMovement($openingEntries, 'in') - $this->sumMovement($openingEntries, 'out');
        $operatingIn = $this->sumMovement($operatingEntries, 'in');
        $operatingOut = $this->sumMovement($operatingEntries, 'out');
        $closing = $opening + $operatingIn - $operatingOut;

        $openingAmount = $this->sumMovementAmount($openingEntries, 'in') - $this->sumMovementAmount($openingEntries, 'out');
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
