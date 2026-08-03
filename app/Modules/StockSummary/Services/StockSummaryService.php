<?php

namespace Modules\StockSummary\Services;

use App\Support\Services\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Godown\Models\Godown;
use Modules\StockItem\Models\StockItem;
use Modules\StockSummary\Contracts\StockSummaryServiceInterface;
use Modules\StockSummary\Models\StockSummary;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;

class StockSummaryService extends BaseService implements StockSummaryServiceInterface
{
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
     * Stock In Hand — item-level summary
     * Formula: Closing Quantity = Opening Quantity + Operating Inward - Operating Outward
     */
    public function stockInHand(): array
    {
        $fiscalYearId = $this->userFiscalYear->fiscal_year_id;

        $items = StockItem::withWhereHas(
            'stock_journal_entries.stock_journal.voucher',
            fn ($q) => $q->where('fiscal_year_id', $fiscalYearId)
        )
            ->with([
                'stock_unit',
                'stock_journal_entries' => function ($q) use ($fiscalYearId) {
                    $q->whereHas(
                        'stock_journal.voucher',
                        fn ($v) => $v->where('fiscal_year_id', $fiscalYearId)
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

        $items = StockItem::with([
            'stock_unit',
            'stock_journal_entries' => function ($query) use ($fiscalYearId) {
                $query->whereHas('stock_journal.voucher', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId);
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

        // Single flat query using direct DB joins instead of deep Eloquent withWhereHas chains
        $rows = DB::table('stock_journal_godown_entries as sjge')
            ->join('stock_journal_entries as sje', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('stock_journals as sj', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('vouchers as v', 'v.stock_journal_id', '=', 'sj.id')
            ->join('godowns as g', 'sjge.godown_id', '=', 'g.id')
            ->join('stock_items as si', 'sje.stock_item_id', '=', 'si.id')
            ->leftJoin('stock_units as su', 'si.stock_unit_id', '=', 'su.id')
            ->where('v.fiscal_year_id', $fiscalYearId)
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

        $items = StockItem::with([
            'stock_unit',
            'stock_journal_entries' => function ($query) use ($fiscalYearId) {
                $query->whereHas('stock_journal.voucher', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId);
                })->with([
                    'stock_journal.voucher.voucher_type',
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

                $voucherCollection[] = [
                    'voucher_id' => $voucher->id,
                    'voucher_type' => $voucher->voucher_type->name,
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

            // Get godown details for this transaction
            $godownDetails = $entries->flatMap(fn ($e) => $e->stock_journal_godown_entries)
                ->groupBy('godown_id')
                ->map(function ($geEntries) {
                    $ge = $geEntries->first();
                    $qtyIn = $this->sumMovement($geEntries, 'in');
                    $qtyOut = $this->sumMovement($geEntries, 'out');

                    return [
                        'godown_id' => $ge->godown_id,
                        'godown_name' => $ge->godown->name ?? null,
                        'inward_quantity' => $qtyIn,
                        'outward_quantity' => $qtyOut,
                        'net_quantity' => $qtyIn - $qtyOut,
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
