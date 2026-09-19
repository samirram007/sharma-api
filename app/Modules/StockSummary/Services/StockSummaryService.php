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

    /**
     * How many pre-window rows a from_date-scoped lifeline may replay to seed
     * its running balances. Batches carry tens of lines, so this is generous;
     * beyond it the lifeline says the seed was truncated instead of quietly
     * starting from the wrong balance.
     */
    private const LIFELINE_SEED_CAP = 20000;

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
    //  Opening Stock report (item-wise / godown-wise)
    // ──────────────────────────────────────────────

    /**
     * Opening Stock report — the stock recorded by the fiscal year's opening
     * voucher(s) (stock journal types 'OPENING' / 'OPNSK'), broken down
     * item-wise and godown-wise with batch detail lines.
     *
     * Optionally narrowed to a single godown and/or item.
     */
    public function getOpeningStockReport(?int $godownId = null, ?int $itemId = null): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        $rows = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
            ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
            ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
            ->leftJoin('godowns as g', 'sjge.godown_id', '=', 'g.id')
            ->where('v.fiscal_year_id', $fiscalYearId)
            ->whereIn('sj.type', ['OPENING', 'OPNSK'])
            ->when($godownId, fn ($q) => $q->where('sjge.godown_id', $godownId))
            ->when($itemId, fn ($q) => $q->where('sje.stock_item_id', $itemId))
            // Respect the purge scopes on both entry levels
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_godown_entry_purges')
                    ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_entry_purges')
                    ->whereColumn('stock_journal_entry_purges.stock_journal_entry_id', 'sje.id');
            })
            ->orderBy('si.name')
            ->orderBy('sjge.batch_no')
            ->selectRaw('
                sje.stock_item_id,
                si.name as item_name,
                si.code as item_code,
                su.code as unit_code,
                su.name as unit_name,
                su.no_of_decimal_places,
                sjge.godown_id,
                g.name as godown_name,
                g.code as godown_code,
                sjge.batch_no,
                sjge.mfg_date,
                sjge.expiry_date,
                sjge.actual_quantity,
                sjge.rate,
                sjge.amount,
                sjge.remarks,
                v.id as voucher_id,
                v.voucher_no,
                v.voucher_date
            ')
            ->get();

        $openingVoucher = DB::table('stock_journals as sj')
            ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
            ->where('v.fiscal_year_id', $fiscalYearId)
            ->whereIn('sj.type', ['OPENING', 'OPNSK'])
            ->orderBy('v.voucher_date')
            ->select('v.id', 'v.voucher_no', 'v.voucher_date')
            ->first();

        // Item-wise: item → godown → batch lines
        $items = [];
        foreach ($rows->groupBy('stock_item_id') as $groupRows) {
            $first = $groupRows->first();
            $godowns = [];
            $itemQty = 0.0;
            $itemAmount = 0.0;

            foreach ($groupRows->groupBy('godown_id') as $godownRows) {
                $godownFirst = $godownRows->first();
                $godownQty = 0.0;
                $godownAmount = 0.0;
                $batches = [];

                foreach ($godownRows as $row) {
                    $qty = (float) $row->actual_quantity;
                    $amount = (float) $row->amount;
                    $godownQty += $qty;
                    $godownAmount += $amount;
                    $batches[] = [
                        'batchNo' => $row->batch_no,
                        'mfgDate' => $this->formatDbDate($row->mfg_date),
                        'expiryDate' => $this->formatDbDate($row->expiry_date),
                        // The opening voucher's date (normally the FY first
                        // day, 1-Apr) — shown as "Opening as on" per row.
                        'openingAsOnDate' => $this->formatDbDate($row->voucher_date),
                        'quantity' => $qty,
                        'rate' => (float) $row->rate,
                        'amount' => $amount,
                        'remarks' => $row->remarks,
                    ];
                }

                $godowns[] = [
                    'godownId' => (int) $godownFirst->godown_id,
                    'godownName' => $godownFirst->godown_name,
                    'godownCode' => $godownFirst->godown_code,
                    'quantity' => $godownQty,
                    'amount' => round($godownAmount, 2),
                    'batches' => $batches,
                ];

                $itemQty += $godownQty;
                $itemAmount += $godownAmount;
            }

            $items[] = [
                'itemId' => (int) $first->stock_item_id,
                'itemName' => $first->item_name,
                'itemCode' => $first->item_code,
                'unitCode' => $first->unit_code,
                'unitName' => $first->unit_name,
                'noOfDecimalPlaces' => (int) ($first->no_of_decimal_places ?? 2),
                'quantity' => $itemQty,
                'amount' => round($itemAmount, 2),
                'godowns' => $godowns,
            ];
        }

        // Godown-wise: godown → item → batch lines
        $godowns = [];
        foreach ($rows->groupBy('godown_id') as $groupRows) {
            $godownFirst = $groupRows->first();
            $godownQty = 0.0;
            $godownAmount = 0.0;
            $itemsInGodown = [];

            foreach ($groupRows->groupBy('stock_item_id') as $itemRows) {
                $itemFirst = $itemRows->first();
                $itemQty = 0.0;
                $itemAmount = 0.0;
                $batches = [];

                foreach ($itemRows as $row) {
                    $qty = (float) $row->actual_quantity;
                    $amount = (float) $row->amount;
                    $itemQty += $qty;
                    $itemAmount += $amount;
                    $batches[] = [
                        'batchNo' => $row->batch_no,
                        'mfgDate' => $this->formatDbDate($row->mfg_date),
                        'expiryDate' => $this->formatDbDate($row->expiry_date),
                        // The opening voucher's date (normally the FY first
                        // day, 1-Apr) — shown as "Opening as on" per row.
                        'openingAsOnDate' => $this->formatDbDate($row->voucher_date),
                        'quantity' => $qty,
                        'rate' => (float) $row->rate,
                        'amount' => $amount,
                    ];
                }

                $itemsInGodown[] = [
                    'itemId' => (int) $itemFirst->stock_item_id,
                    'itemName' => $itemFirst->item_name,
                    'itemCode' => $itemFirst->item_code,
                    'unitCode' => $itemFirst->unit_code,
                    'noOfDecimalPlaces' => (int) ($itemFirst->no_of_decimal_places ?? 2),
                    'quantity' => $itemQty,
                    'amount' => round($itemAmount, 2),
                    'batches' => $batches,
                ];

                $godownQty += $itemQty;
                $godownAmount += $itemAmount;
            }

            usort($itemsInGodown, fn ($a, $b) => strcmp($a['itemName'], $b['itemName']));

            $godowns[] = [
                'godownId' => (int) $godownFirst->godown_id,
                'godownName' => $godownFirst->godown_name,
                'godownCode' => $godownFirst->godown_code,
                'quantity' => $godownQty,
                'amount' => round($godownAmount, 2),
                'items' => $itemsInGodown,
            ];
        }

        usort($godowns, fn ($a, $b) => strcmp($a['godownName'] ?? '', $b['godownName'] ?? ''));

        return [
            'openingVoucher' => $openingVoucher ? [
                'voucherId' => (int) $openingVoucher->id,
                'voucherNo' => $openingVoucher->voucher_no,
                'voucherDate' => $this->formatDbDate($openingVoucher->voucher_date),
            ] : null,
            'items' => $items,
            'godowns' => $godowns,
            'totals' => [
                'itemCount' => count($items),
                'godownCount' => count($godowns),
                'totalQuantity' => round((float) $rows->sum(fn ($r) => (float) $r->actual_quantity), 2),
                'totalAmount' => round((float) $rows->sum(fn ($r) => (float) $r->amount), 2),
            ],
        ];
    }

    // ──────────────────────────────────────────────
    //  Batch Movement report (full movement + balance)
    // ──────────────────────────────────────────────

    /**
     * Distinct batch numbers for the batch-movement report's search dropdown.
     * Returns batch_no + the item/godown contexts they appear in, optionally
     * narrowed by a search string (batch no or item name prefix/substring)
     * and/or a specific item and godown (used by the UI to keep the
     * suggestions consistent with the filters already chosen).
     */
    public function getBatchList(?string $search = null, int $limit = 200, ?int $itemId = null, ?int $godownId = null): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        $rows = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
            ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
            ->leftJoin('godowns as g', 'sjge.godown_id', '=', 'g.id')
            // No FY filter — a batch bought in a previous FY but still in
            // stock (carried forward) must stay searchable here, matching the
            // batch-movements report which builds its Opening from those rows.
            // 'CLOSING' journals are year-end snapshots, not real stock.
            ->where('sj.type', '!=', 'CLOSING')
            ->whereNotNull('sjge.batch_no')
            ->where('sjge.batch_no', '!=', '')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('sjge.batch_no', 'like', "%{$search}%")
                        ->orWhere('si.name', 'like', "%{$search}%");
                });
            })
            ->when($itemId, fn ($q) => $q->where('sje.stock_item_id', $itemId))
            ->when($godownId, fn ($q) => $q->where('sjge.godown_id', $godownId))
            // Purged entry lines (whole-entry purge) must not surface as
            // batch suggestions — parity with the batch-movements report.
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_entry_purges')
                    ->whereColumn('stock_journal_entry_purges.stock_journal_entry_id', 'sje.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_godown_entry_purges')
                    ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
            })
            ->groupBy('sjge.batch_no', 'sje.stock_item_id', 'si.name', 'sjge.godown_id', 'g.name')
            ->orderBy('sjge.batch_no')
            ->limit($limit)
            ->selectRaw('
                sjge.batch_no,
                sje.stock_item_id,
                si.name as item_name,
                sjge.godown_id,
                g.name as godown_name
            ')
            ->get();

        return $rows->map(fn ($row) => [
            'batchNo' => $row->batch_no,
            'itemId' => (int) $row->stock_item_id,
            'itemName' => $row->item_name,
            'godownId' => (int) $row->godown_id,
            'godownName' => $row->godown_name,
        ])->values()->toArray();
    }

    /**
     * Batch Balancing report — per (item, godown, batch): opening → inward →
     * outward → closing with the full chronological movement trail, plus the
     * valuation, ageing and integrity context needed to inspect a batch.
     *
     * Opening semantics mirror the stock-in-hand reports: the 9010 / OPNSK
     * (and 'OPENING') opening-stock voucher — one per fiscal year, dated on
     * the FY first day (1-Apr) — always rolls into the Opening balance, and
     * for a reporting period starting after 1-Apr the Opening additionally
     * folds in every pre-period movement so it reflects the stock position
     * as on the period start. Batches whose prior-year balance never made it
     * into this fiscal year's opening voucher are carried forward from the
     * raw history and flagged (`flags.carryForwardOpening`).
     *
     * The balance aggregation runs in SQL, grouped by (item, godown, batch),
     * so the aggregate filters, sorting and paging all happen at the batch
     * level; the movement trail is then loaded only for the batches on the
     * requested page instead of for the whole result set.
     *
     * Filters: search, item_id, godown_id, batch_no, voucher_type_id,
     * from_date, to_date, expiry_status (all|expired|expiring|valid|none),
     * expiry_within_days (default 90), in_stock_only, issue
     * (all|ok|carry_forward|journal_variance|negative|no_movement), sort_by
     * (item|batch|godown|opening|inward|outward|closing|value|expiry|activity),
     * sort_dir (asc|desc), page, per_page (0 = everything) and
     * include_movements (false = balances only, no trail).
     *
     * Ageing is measured against the window end — the report's as-on date —
     * and echoed back as `ageingAsOnDate`.
     */
    public function getBatchMovements(array $filters = []): array
    {
        $fiscalYearId = (int) $this->userFiscalYear->fiscal_year_id;
        $fy = FiscalYear::find($fiscalYearId);

        $search = trim((string) ($filters['search'] ?? '')) ?: null;
        $itemId = (int) ($filters['item_id'] ?? 0) ?: null;
        $godownId = (int) ($filters['godown_id'] ?? 0) ?: null;
        $batchNo = trim((string) ($filters['batch_no'] ?? '')) ?: null;
        $voucherTypeId = (int) ($filters['voucher_type_id'] ?? 0) ?: null;

        $fromDate = ! empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->startOfDay() : null;
        $toDate = ! empty($filters['to_date']) ? Carbon::parse($filters['to_date'])->endOfDay() : null;

        // The movement window defaults to the fiscal year, but Opening is the
        // TRUE carried-forward balance: rows from PREVIOUS fiscal years (and
        // anything else before the window) roll into it. Batches purchased in
        // FY1 but sold in FY2 may never appear in FY2's opening voucher
        // (hand-made OPNSK covering only some items), so their Opening must
        // be built from the FY1 rows themselves.
        $windowStart = $fromDate ?? ($fy?->start_date ? Carbon::parse($fy->start_date)->startOfDay() : null);
        $windowEnd = $toDate ?? ($fy?->end_date ? Carbon::parse($fy->end_date)->endOfDay() : null);

        // The 9010 / OPNSK opening voucher is dated on the fiscal year's first
        // day — the canonical "opening stock as on 1-Apr" date. Reported so
        // the UI can label the opening row ("Opening as on 01-Apr-2025") even
        // when no reporting period is set (window falls back to FY bounds).
        $fiscalYearStart = $fy?->start_date ? Carbon::parse($fy->start_date)->toDateString() : null;

        $expiringSoonDays = 90;
        $expiryWithinDays = max(1, (int) ($filters['expiry_within_days'] ?? $expiringSoonDays));
        // Ageing is measured against the window end — the report's as-on date
        // (same convention as the closing-stock report). Falls back to today
        // when neither a period nor a fiscal year is resolved.
        $ageingAsOn = ($windowEnd ?? Carbon::now())->toDateString();
        $expiringCutoff = Carbon::parse($ageingAsOn)->addDays($expiryWithinDays)->toDateString();

        $expiryStatus = $filters['expiry_status'] ?? 'all';
        if (! in_array($expiryStatus, ['all', 'expired', 'expiring', 'valid', 'none'], true)) {
            $expiryStatus = 'all';
        }

        $issue = $filters['issue'] ?? 'all';
        if (! in_array($issue, ['all', 'ok', 'carry_forward', 'journal_variance', 'negative', 'no_movement'], true)) {
            $issue = 'all';
        }

        $inStockOnly = filter_var($filters['in_stock_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeMovements = filter_var($filters['include_movements'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $sortBy = $filters['sort_by'] ?? 'item';
        $sortColumns = ['item', 'batch', 'godown', 'opening', 'inward', 'outward', 'closing', 'value', 'expiry', 'activity'];
        if (! in_array($sortBy, $sortColumns, true)) {
            $sortBy = 'item';
        }
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        // Batch-level paging: per_page 0 (or 'all') returns everything.
        $perPage = $filters['per_page'] ?? 100;
        $perPage = $perPage === 'all' ? 0 : (int) $perPage;
        $perPage = $perPage > 0 ? min($perPage, 1000) : 0;
        $page = max(1, (int) ($filters['page'] ?? 1));

        // The date literals below are generated from Carbon here — never from
        // user input — so inlining them keeps the CASE expressions readable.
        $q = fn (string $date): string => "'".$date."'";

        // Window classification, expressed once and reused by every aggregate.
        $ws = $windowStart?->toDateString();
        // Exclusive upper bound: a row dated any time on the window's last day
        // stays inside the window (the substr() comparison's behaviour).
        $we = $windowEnd?->copy()->addDay()->toDateString();

        $inWindow = '('.($ws ? "v.voucher_date >= {$q($ws)}" : '1 = 1').' AND '.($we ? "v.voucher_date < {$q($we)}" : '1 = 1').')';
        $beforeWindow = $ws ? "v.voucher_date < {$q($ws)}" : '1 = 0';
        $isOpenJournal = "sj.type IN ('OPENING', 'OPNSK')";
        // Opening rows: everything before the window (previous fiscal years
        // included) plus the opening-stock journals that fall inside it.
        $isOpeningRow = "(({$beforeWindow}) OR ({$inWindow} AND {$isOpenJournal}))";
        // This fiscal year's opening-stock journal rows — the 9010 voucher's
        // own lines. Deliberately not window-bound: for a mid-year reporting
        // period the 9010 voucher (dated 1-Apr) sits BEFORE the window but is
        // still the journal part of Opening, not a pre-period movement.
        $isJournalRow = "({$isOpenJournal} AND v.fiscal_year_id = {$fiscalYearId})";
        // Ordinary movements inside the window: the only rows that count as
        // Inward / Outward.
        $isWindowMovement = "({$inWindow} AND NOT ({$isOpenJournal}))";
        $signedQty = "CASE WHEN sjge.movement_type = 'in' THEN sjge.actual_quantity ELSE -sjge.actual_quantity END";
        $signedAmount = "CASE WHEN sjge.movement_type = 'in' THEN sjge.amount ELSE -sjge.amount END";
        // Aggregate expressions, defined once and reused verbatim. MariaDB
        // refuses a SELECT alias that is itself a group function when it is
        // referenced inside another expression ("Reference 'opening_value' not
        // supported (reference to group function)"), so the aggregate filters
        // and the ordering must repeat the aggregate rather than its alias.
        $sumOpeningQty = "SUM(CASE WHEN {$isOpeningRow} THEN {$signedQty} ELSE 0 END)";
        $sumJournalQty = "SUM(CASE WHEN {$isJournalRow} THEN {$signedQty} ELSE 0 END)";
        $sumOpeningValue = "SUM(CASE WHEN {$isOpeningRow} THEN {$signedAmount} ELSE 0 END)";
        $sumOpeningRows = "SUM(CASE WHEN ({$isOpeningRow}) AND v.fiscal_year_id = {$fiscalYearId} THEN 1 ELSE 0 END)";
        $sumInwardQty = "SUM(CASE WHEN {$isWindowMovement} AND sjge.movement_type = 'in' THEN sjge.actual_quantity ELSE 0 END)";
        $sumInwardValue = "SUM(CASE WHEN {$isWindowMovement} AND sjge.movement_type = 'in' THEN sjge.amount ELSE 0 END)";
        $sumOutwardQty = "SUM(CASE WHEN {$isWindowMovement} AND sjge.movement_type = 'out' THEN sjge.actual_quantity ELSE 0 END)";
        $sumOutwardValue = "SUM(CASE WHEN {$isWindowMovement} AND sjge.movement_type = 'out' THEN sjge.amount ELSE 0 END)";
        $sumMovementCount = "SUM(CASE WHEN {$isWindowMovement} THEN 1 ELSE 0 END)";
        $maxExpiry = 'MAX(sjge.expiry_date)';
        // Net closing / expiry presence as aggregate expressions (pass 1).
        $netClosingExpr = "({$sumOpeningQty} + {$sumInwardQty} - {$sumOutwardQty})";
        // `expiry_date` is a DATE column: comparing against a real date literal
        // sidesteps the invalid '' / '0000-00-00' conversions (which also cover
        // the "no expiry recorded" rows the frontend treats as null).
        $expiryPresentExpr = "({$maxExpiry} IS NOT NULL AND {$maxExpiry} > '0001-01-01')";
        // The same two checks expressed against pass 1's column aliases — the
        // second aggregation over the derived table may use those freely.
        $netClosing = '(opening_qty + inward_qty - outward_qty)';
        $expiryPresent = "(expiry_date IS NOT NULL AND expiry_date > '0001-01-01')";

        // Shared row source — every pass below differs only in how it groups,
        // projects and filters the aggregated rows.
        $base = function () use ($search, $itemId, $godownId, $batchNo, $voucherTypeId) {
            return DB::table('stock_journal_godown_entries as sjge')
                ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
                ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
                ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
                ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
                ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
                ->leftJoin('godowns as g', 'sjge.godown_id', '=', 'g.id')
                ->leftJoin('voucher_types as vt', 'v.voucher_type_id', '=', 'vt.id')
                // Any fiscal year — rows are classified by date below:
                // pre-window rows (earlier FYs included) roll into Opening,
                // only in-window rows are reported as movements.
                // 'CLOSING' journals are year-end snapshots, not movements —
                // excluding them avoids double-counting when the next FY's
                // OPENING journal also carries the same balance.
                ->where('sj.type', '!=', 'CLOSING')
                ->whereNotNull('sjge.batch_no')
                ->where('sjge.batch_no', '!=', '')
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($narrow) use ($search) {
                        $narrow->where('sjge.batch_no', 'like', "%{$search}%")
                            ->orWhere('si.name', 'like', "%{$search}%");
                    });
                })
                ->when($itemId, fn ($query) => $query->where('sje.stock_item_id', $itemId))
                ->when($godownId, fn ($query) => $query->where('sjge.godown_id', $godownId))
                ->when($batchNo, fn ($query) => $query->where('sjge.batch_no', $batchNo))
                ->when($voucherTypeId, fn ($query) => $query->where('v.voucher_type_id', $voucherTypeId))
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw('1'))
                        ->from('stock_journal_godown_entry_purges')
                        ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
                })
                // Purged entry lines (whole-entry purge) must disappear from
                // the trail too — parity with the opening-stock report.
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw('1'))
                        ->from('stock_journal_entry_purges')
                        ->whereColumn('stock_journal_entry_purges.stock_journal_entry_id', 'sje.id');
                });
        };

        // ── Pass 1: one aggregated row per (item, godown, batch) ────────────
        $grouped = $base()
            ->groupBy('sje.stock_item_id', 'sjge.godown_id', 'sjge.batch_no')
            ->select('sje.stock_item_id', 'sjge.godown_id', 'sjge.batch_no')
            ->selectRaw("
                {$sumOpeningQty} as opening_qty,
                {$sumJournalQty} as journal_qty,
                {$sumOpeningValue} as opening_value,
                {$sumOpeningRows} as opening_detail_count,
                {$sumInwardQty} as inward_qty,
                {$sumInwardValue} as inward_value,
                {$sumOutwardQty} as outward_qty,
                {$sumOutwardValue} as outward_value,
                {$sumMovementCount} as movement_count,
                MIN(CASE WHEN ({$isOpeningRow}) OR ({$isWindowMovement}) THEN v.voucher_date END) as first_movement_date,
                MAX(CASE WHEN {$isWindowMovement} THEN v.voucher_date END) as last_movement_date,
                MAX(sjge.mfg_date) as mfg_date,
                {$maxExpiry} as expiry_date,
                MAX(si.name) as item_name,
                MAX(si.code) as item_code,
                MAX(su.code) as unit_code,
                MAX(COALESCE(su.no_of_decimal_places, 2)) as no_of_decimal_places,
                MAX(g.name) as godown_name,
                MAX(g.code) as godown_code
            ")
            // Pure-history noise has nothing to inspect: no opening, no window
            // movement and no current-FY opening detail row.
            ->havingRaw("NOT ({$sumOpeningQty} = 0 AND {$sumInwardQty} = 0 AND {$sumOutwardQty} = 0 AND {$sumOpeningRows} = 0)");

        if ($inStockOnly) {
            $grouped->havingRaw("{$netClosingExpr} > 0");
        }

        if ($expiryStatus === 'none') {
            $grouped->havingRaw("NOT {$expiryPresentExpr}");
        } elseif ($expiryStatus === 'expired') {
            $grouped->havingRaw("{$expiryPresentExpr} AND {$maxExpiry} < {$q($ageingAsOn)}");
        } elseif ($expiryStatus === 'expiring') {
            $grouped->havingRaw("{$expiryPresentExpr} AND {$maxExpiry} >= {$q($ageingAsOn)} AND {$maxExpiry} < {$q($expiringCutoff)}");
        } elseif ($expiryStatus === 'valid') {
            $grouped->havingRaw("{$expiryPresentExpr} AND {$maxExpiry} >= {$q($expiringCutoff)}");
        }

        $carryForwardIssue = "({$sumOpeningQty} <> 0 AND {$sumOpeningRows} = 0)";
        $journalVarianceIssue = "({$sumOpeningQty} <> {$sumJournalQty})";
        $negativeIssue = "({$netClosingExpr} < 0)";
        $noMovementIssue = "({$sumInwardQty} = 0 AND {$sumOutwardQty} = 0)";

        if ($issue === 'carry_forward') {
            $grouped->havingRaw($carryForwardIssue);
        } elseif ($issue === 'journal_variance') {
            $grouped->havingRaw($journalVarianceIssue);
        } elseif ($issue === 'negative') {
            $grouped->havingRaw($negativeIssue);
        } elseif ($issue === 'no_movement') {
            $grouped->havingRaw($noMovementIssue);
        } elseif ($issue === 'ok') {
            $grouped->havingRaw("NOT {$carryForwardIssue} AND NOT {$journalVarianceIssue} AND NOT {$negativeIssue} AND NOT {$noMovementIssue}");
        }

        // ── Grand totals over every matching batch (not just this page) ─────
        // The grouped set is aggregated a second time in SQL. Counters feed
        // the integrity summary; the value columns feed the valuation cards.
        $totalsRow = DB::query()->fromSub(clone $grouped, 'g')->selectRaw("
            COUNT(*) as batch_count,
            SUM(opening_qty) as total_opening,
            SUM(journal_qty) as total_journal,
            SUM(inward_qty) as total_inward,
            SUM(outward_qty) as total_outward,
            SUM(opening_value) as total_opening_value,
            SUM(inward_value) as total_inward_value,
            SUM(outward_value) as total_outward_value,
            SUM(CASE WHEN opening_qty <> 0 AND opening_detail_count = 0 THEN 1 ELSE 0 END) as carry_forward_count,
            SUM(CASE WHEN opening_qty <> journal_qty THEN 1 ELSE 0 END) as journal_variance_count,
            SUM(CASE WHEN {$netClosing} < 0 THEN 1 ELSE 0 END) as negative_count,
            SUM(CASE WHEN inward_qty = 0 AND outward_qty = 0 THEN 1 ELSE 0 END) as no_movement_count,
            SUM(CASE WHEN {$netClosing} = 0 THEN 1 ELSE 0 END) as zero_closing_count,
            SUM(CASE WHEN {$expiryPresent} AND expiry_date < {$q($ageingAsOn)} THEN 1 ELSE 0 END) as expired_count,
            SUM(CASE WHEN {$expiryPresent} AND expiry_date >= {$q($ageingAsOn)} AND expiry_date < {$q($expiringCutoff)} THEN 1 ELSE 0 END) as expiring_count
        ")->first();

        $totalBatches = (int) ($totalsRow->batch_count ?? 0);
        $totalPages = $perPage > 0 ? max(1, (int) ceil($totalBatches / $perPage)) : 1;
        $page = min($page, $totalPages);

        // ── Pass 2: the requested page of batches, ordered in SQL ───────────
        $pageQuery = clone $grouped;
        switch ($sortBy) {
            case 'batch':
                $pageQuery->orderBy('batch_no', $sortDir);
                break;
            case 'godown':
                $pageQuery->orderBy('godown_name', $sortDir);
                break;
            case 'opening':
                $pageQuery->orderBy('opening_qty', $sortDir);
                break;
            case 'inward':
                $pageQuery->orderBy('inward_qty', $sortDir);
                break;
            case 'outward':
                $pageQuery->orderBy('outward_qty', $sortDir);
                break;
            case 'closing':
                $pageQuery->orderByRaw("{$netClosingExpr} {$sortDir}");
                break;
            case 'value':
                $pageQuery->orderByRaw("({$sumOpeningValue} + {$sumInwardValue} - {$sumOutwardValue}) {$sortDir}");
                break;
            case 'expiry':
                $pageQuery->orderByRaw("{$expiryPresentExpr} DESC, {$maxExpiry} {$sortDir}");
                break;
            case 'activity':
                $pageQuery->orderBy('last_movement_date', $sortDir);
                break;
            default:
                $pageQuery->orderBy('item_name', $sortDir);
                break;
        }
        // Stable tie-breakers keep paging deterministic.
        $pageQuery->orderBy('batch_no')->orderBy('godown_id')->orderBy('stock_item_id');

        if ($perPage > 0) {
            $pageQuery->offset(($page - 1) * $perPage)->limit($perPage);
        }

        $pageRows = $pageQuery->get();

        // ── Shape the page's batches ───────────────────────────────────────
        // The trail key keeps a NULL godown distinct from godown 0 (the trail
        // query below has to match NULLs with whereNull).
        $keyOf = fn (?int $keyItemId, ?int $keyGodownId, ?string $keyBatchNo): string => $keyItemId."\x1F".($keyGodownId ?? '')."\x1F".$keyBatchNo;

        $batches = [];
        $trailIndex = [];

        foreach ($pageRows as $row) {
            $openingQty = round((float) $row->opening_qty, 4);
            $journalQty = round((float) $row->journal_qty, 4);
            $inwardQty = round((float) $row->inward_qty, 4);
            $outwardQty = round((float) $row->outward_qty, 4);
            $closingQty = round($openingQty + $inwardQty - $outwardQty, 4);

            $openingValue = round((float) $row->opening_value, 2);
            $inwardValue = round((float) $row->inward_value, 2);
            $outwardValue = round((float) $row->outward_value, 2);
            $closingValue = round($openingValue + $inwardValue - $outwardValue, 2);

            $mfgDate = $this->formatDbDate($row->mfg_date);
            $expiryDate = $this->formatDbDate($row->expiry_date);
            // Negative when already expired as on the report's as-on date.
            $daysToExpiry = $expiryDate
                ? (int) round(Carbon::parse($ageingAsOn)->diffInDays(Carbon::parse($expiryDate), false))
                : null;
            $daysSinceMfg = $mfgDate
                ? (int) round(Carbon::parse($mfgDate)->diffInDays(Carbon::parse($ageingAsOn), false))
                : null;

            $expiryState = 'none';
            if ($expiryDate) {
                $expiryState = $daysToExpiry <= 0
                    ? 'expired'
                    : ($daysToExpiry < $expiryWithinDays ? 'expiring' : 'valid');
            }

            $flags = [
                'carryForwardOpening' => $openingQty !== 0.0 && (int) $row->opening_detail_count === 0,
                'journalVariance' => $openingQty !== $journalQty,
                'negativeClosing' => $closingQty < 0,
                'noMovement' => $inwardQty === 0.0 && $outwardQty === 0.0,
            ];

            $trailIndex[$keyOf(
                (int) $row->stock_item_id,
                $row->godown_id === null ? null : (int) $row->godown_id,
                $row->batch_no
            )] = count($batches);

            $batches[] = [
                'itemId' => (int) $row->stock_item_id,
                'itemName' => $row->item_name,
                'itemCode' => $row->item_code,
                'unitCode' => $row->unit_code,
                'noOfDecimalPlaces' => (int) ($row->no_of_decimal_places ?? 2),
                'godownId' => (int) $row->godown_id,
                'godownName' => $row->godown_name,
                'godownCode' => $row->godown_code,
                'batchNo' => $row->batch_no,
                'mfgDate' => $mfgDate,
                'expiryDate' => $expiryDate,
                'daysSinceMfg' => $daysSinceMfg,
                'daysToExpiry' => $daysToExpiry,
                'expiryStatus' => $expiryState,
                'openingQuantity' => $openingQty,
                'openingAsPerJournal' => $journalQty,
                'prePeriodNet' => round($openingQty - $journalQty, 4),
                'inwardQuantity' => $inwardQty,
                'outwardQuantity' => $outwardQty,
                'closingQuantity' => $closingQty,
                'openingValue' => $openingValue,
                'inwardValue' => $inwardValue,
                'outwardValue' => $outwardValue,
                'closingValue' => $closingValue,
                'averageRate' => $closingQty !== 0.0 ? round($closingValue / $closingQty, 4) : 0.0,
                'movementCount' => (int) $row->movement_count,
                'firstMovementDate' => $this->formatDbDate($row->first_movement_date),
                'lastMovementDate' => $this->formatDbDate($row->last_movement_date),
                'flags' => $flags,
                'issues' => array_keys(array_filter($flags)),
                'movements' => [],
                'openingMovements' => [],
            ];
        }

        // ── Pass 3: the movement trail, for this page's batches only ────────
        if ($includeMovements && $batches !== []) {
            $trailRows = $base()
                ->where(function ($query) use ($trailIndex) {
                    foreach (array_keys($trailIndex) as $key) {
                        [$keyItem, $keyGodown, $keyBatch] = explode("\x1F", $key, 3);
                        $query->orWhere(function ($narrow) use ($keyItem, $keyGodown, $keyBatch) {
                            $narrow->where('sje.stock_item_id', (int) $keyItem)
                                ->where('sjge.batch_no', $keyBatch);
                            if ($keyGodown === '') {
                                $narrow->whereNull('sjge.godown_id');
                            } else {
                                $narrow->where('sjge.godown_id', (int) $keyGodown);
                            }
                        });
                    }
                })
                ->orderBy('v.voucher_date')
                ->orderBy('v.id')
                ->selectRaw('
                    sje.stock_item_id,
                    sjge.godown_id,
                    sjge.batch_no,
                    sjge.serial_no,
                    sjge.movement_type,
                    sjge.actual_quantity,
                    sjge.rate,
                    sjge.amount,
                    sjge.remarks,
                    sj.type as journal_type,
                    v.id as voucher_id,
                    v.voucher_no,
                    v.voucher_date,
                    v.fiscal_year_id,
                    vt.name as voucher_type_name
                ')
                ->get();

            $trails = [];

            foreach ($trailRows as $row) {
                $key = $keyOf(
                    (int) $row->stock_item_id,
                    $row->godown_id === null ? null : (int) $row->godown_id,
                    $row->batch_no
                );
                if (! isset($trailIndex[$key])) {
                    continue;
                }

                $doc = [
                    'voucherId' => (int) $row->voucher_id,
                    'voucherNo' => $row->voucher_no,
                    'voucherDate' => $row->voucher_date ? substr((string) $row->voucher_date, 0, 10) : null,
                    'voucherTypeName' => $row->voucher_type_name,
                    'movementType' => $row->movement_type,
                    'quantity' => (float) $row->actual_quantity,
                    'rate' => (float) $row->rate,
                    'amount' => (float) $row->amount,
                    'remarks' => $row->remarks,
                    'serialNo' => $row->serial_no ?? null,
                ];

                $date = $doc['voucherDate'];
                // Mirrors the SQL window classification exactly (string
                // comparison of yyyy-MM-dd dates).
                $isBeforeWindow = $ws !== null && $date !== null && $date < $ws;
                $inWindowRow = ($ws === null || ($date !== null && $date >= $ws))
                    && ($we === null || ($date !== null && $date < $we));
                $isJournal = $this->isOpeningType($row->journal_type);

                if ($isBeforeWindow || ($inWindowRow && $isJournal)) {
                    // Current-FY opening rows keep their voucher detail; rows
                    // from earlier fiscal years are balance-only (the prior
                    // year's trail would be noise in a new-year report).
                    if ((int) $row->fiscal_year_id === $fiscalYearId) {
                        // Running balance over the opening rows, in the order
                        // the records arrive (chronological).
                        $balanceAfter = round(
                            ($trails[$key]['openingRunning'] ?? 0.0)
                            + ($row->movement_type === 'in' ? 1 : -1) * (float) $row->actual_quantity,
                            4
                        );

                        $trails[$key]['opening'][] = $doc + [
                            // Same split as the SQL aggregates: this FY's own
                            // opening-stock journal vs every other pre-window
                            // movement folded into Opening.
                            'kind' => ($isJournal && (int) $row->fiscal_year_id === $fiscalYearId) ? 'journal' : 'prePeriod',
                            'balanceAfter' => $balanceAfter,
                        ];
                        $trails[$key]['openingRunning'] = $balanceAfter;
                    }
                } elseif ($inWindowRow) {
                    $trails[$key]['movements'][] = $doc;
                }
                // Rows after the window are ignored entirely.
            }

            foreach ($trailIndex as $key => $index) {
                $openingDetail = $trails[$key]['opening'] ?? [];
                $movements = $trails[$key]['movements'] ?? [];

                usort($openingDetail, fn ($a, $b) => [$a['voucherDate'], $a['voucherNo']] <=> [$b['voucherDate'], $b['voucherNo']]);
                usort($movements, fn ($a, $b) => [$a['voucherDate'], $a['voucherNo']] <=> [$b['voucherDate'], $b['voucherNo']]);

                // Running balance across the window's movements, starting
                // from the batch opening computed in SQL.
                $running = $batches[$index]['openingQuantity'];
                foreach ($movements as &$movement) {
                    $running += $movement['movementType'] === 'in' ? $movement['quantity'] : -$movement['quantity'];
                    $movement['runningBalance'] = round($running, 4);
                }
                unset($movement);

                $batches[$index]['openingMovements'] = $openingDetail;
                $batches[$index]['movements'] = $movements;
            }
        }

        // Voucher types present in the filtered set — powers the voucher-type
        // filter and the voucher-type-wise inspection view.
        $voucherTypes = $base()
            ->groupBy('vt.id', 'vt.name', 'vt.code')
            ->select('vt.id', 'vt.name', 'vt.code')
            ->selectRaw('COUNT(*) as line_count')
            ->orderBy('vt.name')
            ->get()
            ->map(function ($row) {
                // Plain rows from a query builder — read them as arrays so no
                // model type is inferred for the aggregate columns.
                $row = (array) $row;

                return [
                    'id' => $row['id'] === null ? null : (int) $row['id'],
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'lineCount' => (int) $row['line_count'],
                ];
            })
            ->values()
            ->toArray();

        $totalOpening = round((float) ($totalsRow->total_opening ?? 0), 4);
        $totalJournal = round((float) ($totalsRow->total_journal ?? 0), 4);

        return [
            // The 9010 opening voucher's date (the FY first day) — the "opening
            // stock as on" date for the UI's opening-row label.
            'asOnDate' => $fiscalYearStart,
            // Movement window actually used: the explicit from/to filters, or
            // the fiscal-year bounds when the reporting period is unset.
            'fromDate' => $windowStart?->toDateString(),
            'toDate' => $windowEnd?->toDateString(),
            // Ageing reference date + the expiring-soon threshold in days.
            'ageingAsOnDate' => $ageingAsOn,
            'expiryWithinDays' => $expiryWithinDays,
            'batches' => $batches,
            'totals' => [
                'batchCount' => $totalBatches,
                'totalOpening' => $totalOpening,
                'totalOpeningAsPerJournal' => $totalJournal,
                'totalPrePeriodNet' => round($totalOpening - $totalJournal, 4),
                'totalInward' => round((float) ($totalsRow->total_inward ?? 0), 4),
                'totalOutward' => round((float) ($totalsRow->total_outward ?? 0), 4),
                'totalClosing' => round($totalOpening
                    + (float) ($totalsRow->total_inward ?? 0)
                    - (float) ($totalsRow->total_outward ?? 0), 4),
                'totalOpeningValue' => round((float) ($totalsRow->total_opening_value ?? 0), 2),
                'totalInwardValue' => round((float) ($totalsRow->total_inward_value ?? 0), 2),
                'totalOutwardValue' => round((float) ($totalsRow->total_outward_value ?? 0), 2),
                'totalClosingValue' => round(
                    (float) ($totalsRow->total_opening_value ?? 0)
                    + (float) ($totalsRow->total_inward_value ?? 0)
                    - (float) ($totalsRow->total_outward_value ?? 0),
                    2
                ),
                // Integrity counters — feed the summary and the issue filters.
                'carryForwardCount' => (int) ($totalsRow->carry_forward_count ?? 0),
                'journalVarianceCount' => (int) ($totalsRow->journal_variance_count ?? 0),
                'negativeCount' => (int) ($totalsRow->negative_count ?? 0),
                'noMovementCount' => (int) ($totalsRow->no_movement_count ?? 0),
                'zeroClosingCount' => (int) ($totalsRow->zero_closing_count ?? 0),
                'expiredCount' => (int) ($totalsRow->expired_count ?? 0),
                'expiringCount' => (int) ($totalsRow->expiring_count ?? 0),
            ],
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalBatches,
                'totalPages' => $totalPages,
                'hasMore' => $perPage > 0 && $page < $totalPages,
            ],
            'sort' => ['by' => $sortBy, 'dir' => $sortDir],
            'voucherTypes' => $voucherTypes,
        ];
    }

    /**
     * Batch Lifeline — the complete, unbounded history of ONE batch number:
     * every stock-journal line ever booked against it, across items, godowns
     * and fiscal years, in chronological order with running balances.
     *
     * The batch-movements report windows its trail to the reporting period and
     * folds everything older into Opening; the lifeline never collapses
     * history, so the purchase that created the batch, every godown transfer,
     * every issue and every opening / closing journal line stay visible.
     *
     * Row kinds:
     *   - `movement` — an ordinary in / out line; it moves the balance.
     *   - `opening`  — an OPENING / OPNSK journal line. It moves the balance
     *     unless the bucket already carries exactly that quantity, in which
     *     case it only restates the carried-forward position (`applied` false,
     *     `restatesOpening` true) and must not be counted twice.
     *   - `closing`  — a CLOSING year-end snapshot, reported as a marker with
     *     `applied` false: the next year's opening journal already carries that
     *     position, so counting the snapshot too would double the stock.
     *
     * Balances are tracked per (item, godown) bucket — a batch number shared by
     * several items has several balances, because the quantities are in
     * different units.
     *
     * Filters: batch_no (required), item_id, godown_id, from_date, to_date,
     * expiry_within_days, limit (default 1000, max 5000) and
     * include_snapshots (default true).
     */
    public function getBatchLifeline(array $filters = []): array
    {
        $batchNo = trim((string) ($filters['batch_no'] ?? ''));
        $itemId = (int) ($filters['item_id'] ?? 0) ?: null;
        $godownId = (int) ($filters['godown_id'] ?? 0) ?: null;
        $fromDate = ! empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->toDateString() : null;
        $toDate = ! empty($filters['to_date']) ? Carbon::parse($filters['to_date'])->toDateString() : null;
        $includeSnapshots = filter_var($filters['include_snapshots'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $expiryWithinDays = max(1, (int) ($filters['expiry_within_days'] ?? 90));
        $limit = (int) ($filters['limit'] ?? 1000);
        $limit = $limit > 0 ? min($limit, 5000) : 1000;

        $asOn = $toDate ?? Carbon::now()->toDateString();

        // Row source: the same journal line shape the batch-movements report
        // reads, minus its CLOSING exclusion and its window classification —
        // the lifeline wants the raw lines, in date order.
        $rowQuery = function (?string $dateFrom, ?string $dateTo) use ($batchNo, $itemId, $godownId, $includeSnapshots) {
            $query = DB::table('stock_journal_godown_entries as sjge')
                ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
                ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
                ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
                ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
                ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
                ->leftJoin('godowns as g', 'sjge.godown_id', '=', 'g.id')
                ->leftJoin('voucher_types as vt', 'v.voucher_type_id', '=', 'vt.id')
                ->leftJoin('fiscal_years as fy', 'v.fiscal_year_id', '=', 'fy.id')
                ->whereNotNull('sjge.batch_no')
                ->where('sjge.batch_no', '!=', '')
                // Purged lines must disappear from the lifeline too.
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw('1'))
                        ->from('stock_journal_godown_entry_purges')
                        ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
                })
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw('1'))
                        ->from('stock_journal_entry_purges')
                        ->whereColumn('stock_journal_entry_purges.stock_journal_entry_id', 'sje.id');
                })
                ->when($batchNo !== '', fn ($q) => $q->where('sjge.batch_no', $batchNo))
                ->when($itemId, fn ($q) => $q->where('sje.stock_item_id', $itemId))
                ->when($godownId, fn ($q) => $q->where('sjge.godown_id', $godownId))
                ->when($dateFrom, fn ($q) => $q->where('v.voucher_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->where('v.voucher_date', '<=', $dateTo));

            if (! $includeSnapshots) {
                $query->where('sj.type', '!=', 'CLOSING');
            }

            return $query
                ->orderBy('v.voucher_date')
                ->orderBy('v.id')
                ->orderBy('sj.id')
                ->orderBy('sje.entry_order')
                ->orderBy('sjge.entry_order')
                ->select([
                    'sjge.id as godown_entry_id',
                    'sje.stock_item_id',
                    'sjge.godown_id',
                    'sjge.batch_no',
                    'sjge.serial_no',
                    'sjge.movement_type',
                    'sjge.actual_quantity',
                    'sjge.rate',
                    'sjge.amount',
                    'sjge.remarks',
                    'sjge.mfg_date',
                    'sjge.expiry_date',
                    'sj.type as journal_type',
                    'v.id as voucher_id',
                    'v.voucher_no',
                    'v.voucher_date',
                    'v.voucher_type_id',
                    'v.fiscal_year_id',
                    'vt.name as voucher_type_name',
                    'fy.name as fiscal_year_name',
                    'fy.start_date as fiscal_year_start',
                    'fy.end_date as fiscal_year_end',
                    'si.name as item_name',
                    'si.code as item_code',
                    'su.code as unit_code',
                    'su.no_of_decimal_places',
                    'g.name as godown_name',
                    'g.code as godown_code',
                ]);
        };

        if ($batchNo === '') {
            return $this->emptyLifeline($batchNo, $asOn, $limit, $expiryWithinDays, $includeSnapshots, $itemId, $godownId, $fromDate, $toDate);
        }

        $mainRows = $rowQuery($fromDate, $toDate)->limit($limit + 1)->get();
        $truncated = $mainRows->count() > $limit;
        if ($truncated) {
            $mainRows = $mainRows->slice(0, $limit)->values();
        }

        // ── Pre-window history ──────────────────────────────────────────────
        // The lifeline normally starts at the batch's very first line. When a
        // from_date narrows the window, the balances carried into it are
        // replayed from the rows before it so the running balance stays real
        // instead of restarting from zero.
        $seedRows = collect();
        $seedTruncated = false;
        if ($fromDate && ! $truncated) {
            $seedTo = Carbon::parse($fromDate)->subDay()->toDateString();
            $seedCap = self::LIFELINE_SEED_CAP;
            $candidates = $rowQuery(null, $seedTo)->limit($seedCap + 1)->get();
            $seedTruncated = $candidates->count() > $seedCap;
            $seedRows = $seedTruncated ? collect() : $candidates;
        }

        $buckets = [];
        $rows = [];
        $yearOrder = [];

        $sources = $seedRows->isNotEmpty()
            ? [[false, $seedRows], [true, $mainRows]]
            : [[true, $mainRows]];

        foreach ($sources as [$emit, $rawRows]) {
            foreach ($rawRows as $row) {
                $rowItemId = $row->stock_item_id === null ? null : (int) $row->stock_item_id;
                $rowGodownId = $row->godown_id === null ? null : (int) $row->godown_id;
                $key = ($rowItemId ?? 0)."\x1F".($rowGodownId ?? 0);
                $decimals = (int) ($row->no_of_decimal_places ?? 2);
                $tolerance = 0.5 * 10 ** -max(0, min($decimals, 6));

                $bucket = $buckets[$key] ?? [
                    'key' => $key,
                    'itemId' => (int) $rowItemId,
                    'itemName' => $row->item_name,
                    'itemCode' => $row->item_code,
                    'unitCode' => $row->unit_code,
                    'noOfDecimalPlaces' => $decimals,
                    'godownId' => (int) $rowGodownId,
                    'godownName' => $row->godown_name,
                    'godownCode' => $row->godown_code,
                    'balance' => 0.0,
                    'balanceValue' => 0.0,
                    'hasHistory' => false,
                    'inQuantity' => 0.0,
                    'outQuantity' => 0.0,
                    'inValue' => 0.0,
                    'outValue' => 0.0,
                    'openingQuantity' => 0.0,
                    'snapshotQuantity' => null,
                    'rowCount' => 0,
                    'movementCount' => 0,
                    'firstDate' => null,
                    'lastDate' => null,
                    'firstMovementDate' => null,
                    'lastMovementDate' => null,
                    'mfgDate' => null,
                    'expiryDate' => null,
                    'years' => [],
                ];

                $kind = $row->journal_type === 'CLOSING'
                    ? 'closing'
                    : ($this->isOpeningType($row->journal_type) ? 'opening' : 'movement');

                $quantity = round((float) $row->actual_quantity, 4);
                $amount = round((float) $row->amount, 2);
                $isIn = $row->movement_type === 'in';
                $signedQuantity = $kind === 'closing' ? 0.0 : ($isIn ? $quantity : -$quantity);
                $signedAmount = $kind === 'closing' ? 0.0 : ($isIn ? $amount : -$amount);
                $date = $this->formatDbDate($row->voucher_date);

                // An opening journal that merely restates the balance the
                // bucket already carries is a carry-forward, not a receipt.
                $restatesOpening = $kind === 'opening'
                    && $bucket['hasHistory']
                    && abs($signedQuantity - $bucket['balance']) <= $tolerance;
                $applied = $kind !== 'closing' && ! $restatesOpening;

                $balanceBefore = $bucket['balance'];
                $valueBefore = $bucket['balanceValue'];

                if ($applied) {
                    $bucket['balance'] = round($bucket['balance'] + $signedQuantity, 4);
                    $bucket['balanceValue'] = round($bucket['balanceValue'] + $signedAmount, 2);
                    $bucket['hasHistory'] = true;
                }

                $yearKey = $row->fiscal_year_id === null ? 'none' : (string) $row->fiscal_year_id;
                $run = $bucket['years'][$yearKey] ?? [
                    'fiscalYearId' => $row->fiscal_year_id === null ? null : (int) $row->fiscal_year_id,
                    'name' => $row->fiscal_year_name,
                    'startDate' => $this->formatDbDate($row->fiscal_year_start),
                    'endDate' => $this->formatDbDate($row->fiscal_year_end),
                    'carriedIn' => $balanceBefore,
                    'carriedInValue' => $valueBefore,
                    'carriedOut' => $balanceBefore,
                    'carriedOutValue' => $valueBefore,
                    'inQuantity' => 0.0,
                    'outQuantity' => 0.0,
                    'inValue' => 0.0,
                    'outValue' => 0.0,
                    'openingQuantity' => 0.0,
                    'openingRestated' => false,
                    'rowCount' => 0,
                    'movementCount' => 0,
                ];

                if ($kind === 'movement') {
                    $run['movementCount']++;
                    if ($isIn) {
                        $run['inQuantity'] = round($run['inQuantity'] + $quantity, 4);
                        $run['inValue'] = round($run['inValue'] + $amount, 2);
                    } else {
                        $run['outQuantity'] = round($run['outQuantity'] + $quantity, 4);
                        $run['outValue'] = round($run['outValue'] + $amount, 2);
                    }
                } elseif ($kind === 'opening') {
                    $run['openingQuantity'] = round($run['openingQuantity'] + $quantity, 4);
                    if ($restatesOpening) {
                        $run['openingRestated'] = true;
                    }
                }

                // The year's closing position is the bucket balance after its
                // last line — seed rows included, so a window opened mid-year
                // still carries the right opening for that year.
                $run['carriedOut'] = $bucket['balance'];
                $run['carriedOutValue'] = $bucket['balanceValue'];

                if ($emit) {
                    $run['rowCount']++;
                    $bucket['rowCount']++;
                    if ($kind === 'movement') {
                        $bucket['movementCount']++;
                        if ($isIn) {
                            $bucket['inQuantity'] = round($bucket['inQuantity'] + $quantity, 4);
                            $bucket['inValue'] = round($bucket['inValue'] + $amount, 2);
                        } else {
                            $bucket['outQuantity'] = round($bucket['outQuantity'] + $quantity, 4);
                            $bucket['outValue'] = round($bucket['outValue'] + $amount, 2);
                        }
                        if ($bucket['firstMovementDate'] === null) {
                            $bucket['firstMovementDate'] = $date;
                        }
                        if ($date !== null && ($bucket['lastMovementDate'] === null || $date > $bucket['lastMovementDate'])) {
                            $bucket['lastMovementDate'] = $date;
                        }
                    } elseif ($kind === 'opening') {
                        $bucket['openingQuantity'] = round($bucket['openingQuantity'] + $quantity, 4);
                    } else {
                        $bucket['snapshotQuantity'] = $quantity;
                    }

                    if ($date !== null) {
                        if ($bucket['firstDate'] === null) {
                            $bucket['firstDate'] = $date;
                        }
                        $bucket['lastDate'] = $date;
                    }

                    $mfgDate = $this->formatDbDate($row->mfg_date);
                    $expiryDate = $this->formatDbDate($row->expiry_date);
                    if ($mfgDate !== null && ($bucket['mfgDate'] === null || $mfgDate > $bucket['mfgDate'])) {
                        $bucket['mfgDate'] = $mfgDate;
                    }
                    if ($expiryDate !== null && ($bucket['expiryDate'] === null || $expiryDate > $bucket['expiryDate'])) {
                        $bucket['expiryDate'] = $expiryDate;
                    }
                }

                $bucket['years'][$yearKey] = $run;
                $buckets[$key] = $bucket;

                if (! $emit) {
                    continue;
                }

                if (! in_array($yearKey, $yearOrder, true)) {
                    $yearOrder[] = $yearKey;
                }

                $rows[] = [
                    'key' => $key.':'.(int) ($row->godown_entry_id ?? 0),
                    'bucketKey' => $key,
                    'itemId' => (int) $rowItemId,
                    'itemName' => $row->item_name,
                    'itemCode' => $row->item_code,
                    'unitCode' => $row->unit_code,
                    'noOfDecimalPlaces' => $decimals,
                    'godownId' => (int) $rowGodownId,
                    'godownName' => $row->godown_name,
                    'godownCode' => $row->godown_code,
                    'batchNo' => $row->batch_no,
                    'mfgDate' => $this->formatDbDate($row->mfg_date),
                    'expiryDate' => $this->formatDbDate($row->expiry_date),
                    'serialNo' => $row->serial_no ?? null,
                    'remarks' => $row->remarks,
                    'voucherId' => (int) $row->voucher_id,
                    'voucherNo' => $row->voucher_no,
                    'voucherDate' => $date,
                    'voucherTypeId' => $row->voucher_type_id === null ? null : (int) $row->voucher_type_id,
                    'voucherTypeName' => $row->voucher_type_name,
                    'journalType' => $row->journal_type,
                    'kind' => $kind,
                    'movementType' => $row->movement_type,
                    'quantity' => $quantity,
                    'rate' => round((float) $row->rate, 4),
                    'amount' => $amount,
                    'signedQuantity' => $signedQuantity,
                    'signedAmount' => $signedAmount,
                    'applied' => $applied,
                    'restatesOpening' => $restatesOpening,
                    'runningBalance' => $bucket['balance'],
                    'runningValue' => $bucket['balanceValue'],
                    'fiscalYearId' => $run['fiscalYearId'],
                    'fiscalYearName' => $run['name'],
                    'yearKey' => $yearKey,
                    'transfer' => false,
                    'transferPeerGodownId' => null,
                    'transferPeerGodownName' => null,
                ];
            }
        }

        $years = [];
        foreach ($yearOrder as $yearKey) {
            $yearBuckets = [];
            foreach ($buckets as $bucket) {
                $run = $bucket['years'][$yearKey] ?? null;
                if ($run === null || $run['rowCount'] === 0) {
                    continue;
                }
                $yearBuckets[] = [
                    'key' => $bucket['key'],
                    'fiscalYearId' => $run['fiscalYearId'],
                    'name' => $run['name'],
                    'startDate' => $run['startDate'],
                    'endDate' => $run['endDate'],
                    'itemId' => $bucket['itemId'],
                    'itemName' => $bucket['itemName'],
                    'itemCode' => $bucket['itemCode'],
                    'unitCode' => $bucket['unitCode'],
                    'noOfDecimalPlaces' => $bucket['noOfDecimalPlaces'],
                    'godownId' => $bucket['godownId'],
                    'godownName' => $bucket['godownName'],
                    'godownCode' => $bucket['godownCode'],
                    'carriedIn' => $run['carriedIn'],
                    'carriedInValue' => $run['carriedInValue'],
                    'carriedOut' => $run['carriedOut'],
                    'carriedOutValue' => $run['carriedOutValue'],
                    'inQuantity' => $run['inQuantity'],
                    'outQuantity' => $run['outQuantity'],
                    'inValue' => $run['inValue'],
                    'outValue' => $run['outValue'],
                    'openingQuantity' => $run['openingQuantity'],
                    'openingRestated' => $run['openingRestated'],
                    'rowCount' => $run['rowCount'],
                    'movementCount' => $run['movementCount'],
                ];
            }

            if ($yearBuckets === []) {
                continue;
            }

            $sum = fn (string $field): float => round(array_sum(array_map(fn (array $bucket) => (float) $bucket[$field], $yearBuckets)), 4);
            $sumValue = fn (string $field): float => round(array_sum(array_map(fn (array $bucket) => (float) $bucket[$field], $yearBuckets)), 2);

            $years[] = [
                'key' => $yearKey,
                'fiscalYearId' => $yearBuckets[0]['fiscalYearId'] ?? ($yearKey === 'none' ? null : (int) $yearKey),
                'name' => $yearBuckets[0]['name'] ?? null,
                'startDate' => $yearBuckets[0]['startDate'] ?? null,
                'endDate' => $yearBuckets[0]['endDate'] ?? null,
                'rowCount' => (int) array_sum(array_map(fn (array $bucket) => $bucket['rowCount'], $yearBuckets)),
                'movementCount' => (int) array_sum(array_map(fn (array $bucket) => $bucket['movementCount'], $yearBuckets)),
                // Position the year opens and closes on, summed over the year's
                // buckets — read alongside `hasMixedUnits`, which says whether
                // the quantity totals are safe to add up.
                'carriedIn' => $sum('carriedIn'),
                'carriedInValue' => $sumValue('carriedInValue'),
                'carriedOut' => $sum('carriedOut'),
                'carriedOutValue' => $sumValue('carriedOutValue'),
                'inQuantity' => $sum('inQuantity'),
                'outQuantity' => $sum('outQuantity'),
                'inValue' => $sumValue('inValue'),
                'outValue' => $sumValue('outValue'),
                'openingQuantity' => $sum('openingQuantity'),
                'openingRestated' => (bool) array_filter(array_map(fn (array $bucket) => $bucket['openingRestated'], $yearBuckets)),
                // Quantities across buckets live in different units — the UI
                // must not add them, so say so explicitly.
                'hasMixedUnits' => count($yearBuckets) > 1 && ($sum('inQuantity') !== 0.0 || $sum('outQuantity') !== 0.0 || $sum('carriedIn') !== 0.0 || $sum('carriedOut') !== 0.0),
                'buckets' => $yearBuckets,
            ];
        }

        // ── Godown transfers ────────────────────────────────────────────────
        // A transfer voucher books the same batch out of one godown and into
        // another; flag both legs so the timeline can pair them up.
        $byVoucher = [];
        foreach ($rows as $index => $row) {
            if ($row['kind'] !== 'movement') {
                continue;
            }
            $byVoucher[$row['voucherId']][] = $index;
        }

        foreach ($byVoucher as $indexes) {
            $legs = array_filter($indexes, fn (int $index) => $rows[$index]['itemId'] === $rows[$indexes[0]]['itemId']);
            if (count($legs) < 2) {
                continue;
            }

            $outs = array_values(array_filter($legs, fn (int $index) => $rows[$index]['movementType'] === 'out'));
            $ins = array_values(array_filter($legs, fn (int $index) => $rows[$index]['movementType'] === 'in'));
            if ($outs === [] || $ins === []) {
                continue;
            }

            foreach ($outs as $outIndex) {
                foreach ($ins as $inIndex) {
                    if ($rows[$outIndex]['godownId'] === $rows[$inIndex]['godownId']) {
                        continue;
                    }
                    $rows[$outIndex]['transfer'] = true;
                    $rows[$outIndex]['transferPeerGodownId'] = $rows[$inIndex]['godownId'];
                    $rows[$outIndex]['transferPeerGodownName'] = $rows[$inIndex]['godownName'];
                    $rows[$inIndex]['transfer'] = true;
                    $rows[$inIndex]['transferPeerGodownId'] = $rows[$outIndex]['godownId'];
                    $rows[$inIndex]['transferPeerGodownName'] = $rows[$outIndex]['godownName'];
                }
            }
        }

        $bucketSummaries = [];
        foreach ($buckets as $bucket) {
            $expiryDate = $bucket['expiryDate'];
            $mfgDate = $bucket['mfgDate'];
            $daysToExpiry = $expiryDate
                ? (int) round(Carbon::parse($asOn)->diffInDays(Carbon::parse($expiryDate), false))
                : null;
            $daysSinceMfg = $mfgDate
                ? (int) round(Carbon::parse($mfgDate)->diffInDays(Carbon::parse($asOn), false))
                : null;
            $expiryState = 'none';
            if ($expiryDate) {
                $expiryState = $daysToExpiry <= 0
                    ? 'expired'
                    : ($daysToExpiry < $expiryWithinDays ? 'expiring' : 'valid');
            }

            $bucketSummaries[] = [
                'key' => $bucket['key'],
                'itemId' => $bucket['itemId'],
                'itemName' => $bucket['itemName'],
                'itemCode' => $bucket['itemCode'],
                'unitCode' => $bucket['unitCode'],
                'noOfDecimalPlaces' => $bucket['noOfDecimalPlaces'],
                'godownId' => $bucket['godownId'],
                'godownName' => $bucket['godownName'],
                'godownCode' => $bucket['godownCode'],
                'mfgDate' => $mfgDate,
                'expiryDate' => $expiryDate,
                'daysSinceMfg' => $daysSinceMfg,
                'daysToExpiry' => $daysToExpiry,
                'expiryStatus' => $expiryState,
                'openingQuantity' => $bucket['openingQuantity'],
                'snapshotQuantity' => $bucket['snapshotQuantity'],
                'inQuantity' => $bucket['inQuantity'],
                'outQuantity' => $bucket['outQuantity'],
                'inValue' => $bucket['inValue'],
                'outValue' => $bucket['outValue'],
                'closingQuantity' => $bucket['balance'],
                'closingValue' => $bucket['balanceValue'],
                'rowCount' => $bucket['rowCount'],
                'movementCount' => $bucket['movementCount'],
                'firstDate' => $bucket['firstDate'],
                'lastDate' => $bucket['lastDate'],
                'firstMovementDate' => $bucket['firstMovementDate'],
                'lastMovementDate' => $bucket['lastMovementDate'],
            ];
        }

        $totals = [
            'rowCount' => count($rows),
            'movementCount' => 0,
            'openingRowCount' => 0,
            'snapshotRowCount' => 0,
            'restatedOpeningCount' => 0,
            'transferCount' => 0,
            'bucketCount' => count($bucketSummaries),
            'inQuantity' => 0.0,
            'outQuantity' => 0.0,
            'inValue' => 0.0,
            'outValue' => 0.0,
            'closingQuantity' => 0.0,
            'closingValue' => 0.0,
            'firstDate' => null,
            'lastDate' => null,
            'firstMovementDate' => null,
            'lastMovementDate' => null,
        ];

        foreach ($rows as $row) {
            if ($row['kind'] === 'movement') {
                $totals['movementCount']++;
                if ($row['movementType'] === 'in') {
                    $totals['inQuantity'] = round($totals['inQuantity'] + $row['quantity'], 4);
                    $totals['inValue'] = round($totals['inValue'] + $row['amount'], 2);
                } else {
                    $totals['outQuantity'] = round($totals['outQuantity'] + $row['quantity'], 4);
                    $totals['outValue'] = round($totals['outValue'] + $row['amount'], 2);
                }
            } elseif ($row['kind'] === 'opening') {
                $totals['openingRowCount']++;
            } else {
                $totals['snapshotRowCount']++;
            }

            if ($row['restatesOpening']) {
                $totals['restatedOpeningCount']++;
            }
            if ($row['transfer']) {
                $totals['transferCount']++;
            }

            $date = $row['voucherDate'];
            if ($date !== null) {
                if ($totals['firstDate'] === null || $date < $totals['firstDate']) {
                    $totals['firstDate'] = $date;
                }
                if ($totals['lastDate'] === null || $date > $totals['lastDate']) {
                    $totals['lastDate'] = $date;
                }
            }
            if ($row['kind'] === 'movement' && $date !== null) {
                if ($totals['firstMovementDate'] === null || $date < $totals['firstMovementDate']) {
                    $totals['firstMovementDate'] = $date;
                }
                if ($totals['lastMovementDate'] === null || $date > $totals['lastMovementDate']) {
                    $totals['lastMovementDate'] = $date;
                }
            }
        }

        foreach ($bucketSummaries as $bucket) {
            $totals['closingQuantity'] = round($totals['closingQuantity'] + $bucket['closingQuantity'], 4);
            $totals['closingValue'] = round($totals['closingValue'] + $bucket['closingValue'], 2);
        }

        return [
            'batchNo' => $batchNo,
            'asOnDate' => $asOn,
            'rows' => $rows,
            'years' => $years,
            'buckets' => $bucketSummaries,
            'totals' => $totals,
            'meta' => [
                'rowCount' => count($rows),
                'limit' => $limit,
                'truncated' => $truncated,
                'seedRowCount' => $seedRows->count(),
                'seedTruncated' => $seedTruncated,
                'includesClosingSnapshots' => $includeSnapshots,
                'expiryWithinDays' => $expiryWithinDays,
                'filters' => [
                    'itemId' => $itemId,
                    'godownId' => $godownId,
                    'fromDate' => $fromDate,
                    'toDate' => $toDate,
                    'batchNo' => $batchNo,
                ],
            ],
        ];
    }

    /** Shape returned when no batch number is supplied — never a whole ledger. */
    protected function emptyLifeline(
        string $batchNo,
        string $asOn,
        int $limit,
        int $expiryWithinDays,
        bool $includeSnapshots,
        ?int $itemId,
        ?int $godownId,
        ?string $fromDate,
        ?string $toDate,
    ): array {
        return [
            'batchNo' => $batchNo,
            'asOnDate' => $asOn,
            'rows' => [],
            'years' => [],
            'buckets' => [],
            'totals' => [
                'rowCount' => 0,
                'movementCount' => 0,
                'openingRowCount' => 0,
                'snapshotRowCount' => 0,
                'restatedOpeningCount' => 0,
                'transferCount' => 0,
                'bucketCount' => 0,
                'inQuantity' => 0.0,
                'outQuantity' => 0.0,
                'inValue' => 0.0,
                'outValue' => 0.0,
                'closingQuantity' => 0.0,
                'closingValue' => 0.0,
                'firstDate' => null,
                'lastDate' => null,
                'firstMovementDate' => null,
                'lastMovementDate' => null,
            ],
            'meta' => [
                'rowCount' => 0,
                'limit' => $limit,
                'truncated' => false,
                'seedRowCount' => 0,
                'seedTruncated' => false,
                'includesClosingSnapshots' => $includeSnapshots,
                'expiryWithinDays' => $expiryWithinDays,
                'filters' => [
                    'itemId' => $itemId,
                    'godownId' => $godownId,
                    'fromDate' => $fromDate,
                    'toDate' => $toDate,
                    'batchNo' => $batchNo,
                ],
            ],
        ];
    }

    /**
     * Opening-voucher completeness audit — batches whose prior-FY balance is
     * missing from the current fiscal year's opening voucher.
     *
     * For every batch (item + godown + batch_no) with a non-zero net balance
     * built from rows dated BEFORE the fiscal year start, compare it with what
     * this FY's opening voucher (OPENING / OPNSK journals) actually recorded
     * for the same batch. Any shortfall — including batches entirely absent
     * from the voucher — is listed so the opening voucher can be reconciled.
     *
     * Reports mirror the batch-movements report: carry-forward-only batches
     * still show correct running balances (history-based opening), but they
     * indicate the books' opening voucher disagrees with the ledger history.
     *
     * Optional filters narrow the audit to one item / godown / batch.
     */
    public function getMissingOpeningVoucherBatches(array $filters = []): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;
        $fy = FiscalYear::find($fiscalYearId);

        $itemId = isset($filters['item_id']) ? (int) $filters['item_id'] : null;
        $godownId = isset($filters['godown_id']) ? (int) $filters['godown_id'] : null;
        $batchNo = $filters['batch_no'] ?? null;

        if (! $fy) {
            return [
                'fiscalYear' => null,
                'items' => [],
                'totals' => ['missingCount' => 0, 'coveredCount' => 0, 'totalShortfall' => 0.0, 'totalPriorValue' => 0.0],
            ];
        }

        $fyStart = Carbon::parse($fy->start_date)->toDateString();

        $base = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'sj.id', '=', 'v.stock_journal_id')
            // 'CLOSING' journals are year-end snapshots, not real stock —
            // same exclusion as the batch-movements report.
            ->where('sj.type', '!=', 'CLOSING')
            ->whereNotNull('sjge.batch_no')
            ->where('sjge.batch_no', '!=', '')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_entry_purges')
                    ->whereColumn('stock_journal_entry_purges.stock_journal_entry_id', 'sje.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('stock_journal_godown_entry_purges')
                    ->whereColumn('stock_journal_godown_entry_purges.stock_journal_godown_entry_id', 'sjge.id');
            })
            ->when($itemId, fn ($q) => $q->where('sje.stock_item_id', $itemId))
            ->when($godownId, fn ($q) => $q->where('sjge.godown_id', $godownId))
            ->when($batchNo, fn ($q) => $q->where('sjge.batch_no', $batchNo));

        // Prior-FY net balance per batch — everything dated before the FY
        // start (previous fiscal years included). This is the Opening the
        // batch-movements report would compute for a full-FY window.
        $priorRows = (clone $base)
            ->where('v.voucher_date', '<', $fyStart)
            ->groupBy('sje.stock_item_id', 'sjge.godown_id', 'sjge.batch_no')
            ->selectRaw("
                sje.stock_item_id as item_id,
                sjge.godown_id as godown_id,
                sjge.batch_no as batch_no,
                SUM(CASE WHEN sjge.movement_type = 'in' THEN sjge.actual_quantity ELSE -sjge.actual_quantity END) as prior_qty,
                SUM(CASE WHEN sjge.movement_type = 'in' THEN sjge.amount ELSE -sjge.amount END) as prior_amount")
            ->get();

        // What this FY's opening voucher recorded per batch.
        $openingRows = (clone $base)
            ->where('v.fiscal_year_id', $fiscalYearId)
            ->whereIn('sj.type', ['OPENING', 'OPNSK'])
            ->groupBy('sje.stock_item_id', 'sjge.godown_id', 'sjge.batch_no')
            ->selectRaw("
                sje.stock_item_id as item_id,
                sjge.godown_id as godown_id,
                sjge.batch_no as batch_no,
                SUM(CASE WHEN sjge.movement_type = 'in' THEN sjge.actual_quantity ELSE -sjge.actual_quantity END) as opening_qty")
            ->get()
            ->keyBy(fn ($r) => $r->item_id.'|'.$r->godown_id.'|'.$r->batch_no);

        $missing = [];
        $coveredCount = 0;

        foreach ($priorRows as $row) {
            $key = $row->item_id.'|'.$row->godown_id.'|'.$row->batch_no;
            $priorQty = round((float) $row->prior_qty, 4);
            if ($priorQty == 0.0) {
                continue;
            }

            $openingQty = isset($openingRows[$key]) ? round((float) $openingRows[$key]->opening_qty, 4) : 0.0;
            if ($openingQty >= $priorQty - 0.0001) {
                $coveredCount++;

                continue;
            }

            $missing[] = [
                'itemId' => (int) $row->item_id,
                'godownId' => (int) $row->godown_id,
                'batchNo' => $row->batch_no,
                'priorQty' => $priorQty,
                'openingQty' => $openingQty,
                'shortfallQty' => round($priorQty - $openingQty, 4),
                'priorAmount' => round((float) $row->prior_amount, 2),
            ];
        }

        // Master-data context for the reconciliation screen.
        $itemIds = array_unique(array_column($missing, 'itemId'));
        $godownIds = array_unique(array_column($missing, 'godownId'));
        $itemsById = StockItem::whereIn('id', $itemIds)
            ->with('stock_unit:id,code,name')
            ->get()
            ->keyBy('id');
        $godownsById = Godown::whereIn('id', $godownIds)->get()->keyBy('id');

        $items = array_map(function ($m) use ($itemsById, $godownsById) {
            $item = $itemsById->get($m['itemId']);
            $godown = $godownsById->get($m['godownId']);

            return $m + [
                'itemCode' => $item?->code,
                'itemName' => $item?->name,
                'unitCode' => $item?->stock_unit?->code,
                'godownName' => $godown?->name,
                'godownCode' => $godown?->code,
            ];
        }, $missing);

        usort($items, fn ($a, $b) => [$a['itemName'], $a['godownName'], $a['batchNo']] <=> [$b['itemName'], $b['godownName'], $b['batchNo']]);

        return [
            'fiscalYear' => [
                'id' => $fy->id,
                'name' => $fy->name,
                'startDate' => $fyStart,
                'endDate' => Carbon::parse($fy->end_date)->toDateString(),
            ],
            'items' => $items,
            'totals' => [
                'missingCount' => count($items),
                'coveredCount' => $coveredCount,
                'totalShortfall' => round(array_sum(array_column($items, 'shortfallQty')), 4),
                'totalPriorValue' => round(array_sum(array_column($items, 'priorAmount')), 2),
            ],
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
