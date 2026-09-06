<?php

namespace Modules\Dashboard\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Dashboard\Contracts\DashboardServiceInterface;
use Modules\Distributor\Models\Distributor;
use Modules\FiscalYear\Models\FiscalYear;
use Modules\Freight\Contracts\FreightServiceInterface;
use Modules\Godown\Models\Godown;
use Modules\Transporter\Models\Transporter;
use Modules\User\Models\User;
use Modules\Voucher\Models\Voucher;

class DashboardService extends BaseService implements DashboardServiceInterface
{
    protected string $modelClass = Voucher::class;

    protected int $deliveryNoteVoucherTypeId = 2001; // delivery note

    protected int $receiptNoteVoucherTypeId = 2002; // receipt note

    protected int $salesVoucherTypeId = 1006; // freight (sales) voucher

    public function __construct(protected FreightServiceInterface $freightService) {}

    /**
     * Aggregated summary used for the stat cards.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getDashboardFiscalYearPeriod();

        $freightQuery = Voucher::where('vouchers.module', 'freight')
            ->where('vouchers.voucher_type_id', $this->salesVoucherTypeId)
            ->when($fiscalYearId, fn ($q) => $q->where('vouchers.fiscal_year_id', $fiscalYearId))
            ->when($startDate, fn ($q) => $q->whereBetween('vouchers.voucher_date', [$startDate, $endDate]));

        $freightCount = (clone $freightQuery)->count();

        // Sum the credit side of freight voucher entries (= total fare billed)
        $freightTotalFare = (float) (clone $freightQuery)
            ->join('voucher_entries', 'voucher_entries.voucher_id', '=', 'vouchers.id')
            ->sum('voucher_entries.credit');

        $deliveryNoteCount = Voucher::where('vouchers.voucher_type_id', $this->deliveryNoteVoucherTypeId)
            ->whereNotNull('vouchers.stock_journal_id')
            ->when($fiscalYearId, fn ($q) => $q->where('vouchers.fiscal_year_id', $fiscalYearId))
            ->when($startDate, fn ($q) => $q->whereBetween('vouchers.voucher_date', [$startDate, $endDate]))
            ->count();

        $receiptNoteCount = Voucher::where('vouchers.voucher_type_id', $this->receiptNoteVoucherTypeId)
            ->when($fiscalYearId, fn ($q) => $q->where('vouchers.fiscal_year_id', $fiscalYearId))
            ->when($startDate, fn ($q) => $q->whereBetween('vouchers.voucher_date', [$startDate, $endDate]))
            ->count();

        [$paymentCount, $paymentTotal] = $this->paymentStats($fiscalYearId, $startDate, $endDate);

        $currentFiscalYear = $fiscalYearId ? FiscalYear::find($fiscalYearId)?->name : null;

        return [
            'transporterCount' => Transporter::count(),
            'distributorCount' => Distributor::count(),
            'zoneCount' => Godown::where('storage_unit_type', 'ZONE')->count(),
            'godownCount' => Godown::count(),
            'userCount' => User::count(),
            'freightCount' => $freightCount,
            'freightTotalFare' => round($freightTotalFare, 2),
            'deliveryNoteCount' => $deliveryNoteCount,
            'receiptNoteCount' => $receiptNoteCount,
            'paymentCount' => $paymentCount,
            'paymentTotal' => round($paymentTotal, 2),
            'currentFiscalYear' => $currentFiscalYear,
        ];
    }

    /**
     * Payments made against freight vouchers (type = 'freight_payment').
     *
     * @return array{0: int, 1: float} [count, total_amount]
     */
    protected function paymentStats(?int $fiscalYearId, $startDate, $endDate): array
    {
        $paymentVoucherIds = DB::table('voucher_references')
            ->where('type', 'freight_payment')
            ->pluck('voucher_id');

        if ($paymentVoucherIds->isEmpty()) {
            return [0, 0.0];
        }

        $query = Voucher::whereIn('vouchers.id', $paymentVoucherIds)
            ->when($fiscalYearId, fn ($q) => $q->where('vouchers.fiscal_year_id', $fiscalYearId))
            ->when($startDate, fn ($q) => $q->whereBetween('vouchers.voucher_date', [$startDate, $endDate]));

        $count = (clone $query)->count();

        $total = (float) (clone $query)
            ->join('voucher_entries', 'voucher_entries.voucher_id', '=', 'vouchers.id')
            ->sum('voucher_entries.credit');

        return [$count, $total];
    }

    public function zoneWise(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getDashboardFiscalYearPeriod();
        if (! $fiscalYearId) {
            return new Collection;
        }

        return $this->stripDetailRows(
            $this->freightService->zoneWiseReport($startDate, $endDate)
        );
    }

    public function godownWise(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getDashboardFiscalYearPeriod();
        if (! $fiscalYearId) {
            return new Collection;
        }

        // Aggregate in SQL instead of reusing godownWiseReport(): the report
        // materialises one detail row per stock-journal godown entry (whole FY ≈
        // tens of thousands of rows) which the dashboard never displays — that
        // made this endpoint take ~90s / 4 MB. A grouped query returns the same
        // totals in seconds.
        //
        // MariaDB ignores idx_vouchers_type_fy_date (the FY filter matches ~89%
        // of rows, so the planner prefers a full scan + join buffer and takes
        // ~40s); the hint keeps it on the composite index created by the
        // `add_freight_list_performance_indexes` migration.
        $from = 'vouchers as v';
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $from .= ' FORCE INDEX (idx_vouchers_type_fy_date)';
        }

        $rows = DB::table(DB::raw($from))
            ->join('stock_journals as sj', 'sj.id', '=', 'v.stock_journal_id')
            ->join('stock_journal_entries as sje', 'sje.stock_journal_id', '=', 'sj.id')
            ->join('stock_journal_godown_entries as sjge', 'sjge.stock_journal_entry_id', '=', 'sje.id')
            ->join('godowns as g', 'g.id', '=', 'sjge.godown_id')
            ->where('v.voucher_type_id', $this->deliveryNoteVoucherTypeId)
            ->whereNotNull('v.stock_journal_id')
            ->where('v.fiscal_year_id', $fiscalYearId)
            ->whereBetween('v.voucher_date', [$startDate, $endDate])
            ->select(
                'g.id as godown_id',
                'g.name as godown_name',
                'g.code as godown_code',
                DB::raw('COUNT(DISTINCT v.id) as total_entries'),
                DB::raw("COALESCE(SUM(CASE WHEN sjge.movement_type = 'in' THEN sjge.actual_quantity ELSE 0 END), 0) as total_inward_quantity"),
                DB::raw("COALESCE(SUM(CASE WHEN sjge.movement_type = 'out' THEN sjge.actual_quantity ELSE 0 END), 0) as total_outward_quantity"),
                DB::raw('COALESCE(SUM(sjge.amount), 0) as total_amount')
            )
            ->groupBy('g.id', 'g.name', 'g.code')
            ->orderByDesc('total_amount')
            ->get();

        $result = $rows->map(function ($row) {
            $inward = (float) $row->total_inward_quantity;
            $outward = (float) $row->total_outward_quantity;

            return [
                'godownId' => (int) $row->godown_id,
                'godownName' => (string) $row->godown_name,
                'godownCode' => (string) $row->godown_code,
                'totalEntries' => (int) $row->total_entries,
                'totalInwardQuantity' => round($inward, 4),
                'totalOutwardQuantity' => round($outward, 4),
                'totalClosingQuantity' => round($inward - $outward, 4),
                'totalAmount' => round((float) $row->total_amount, 2),
            ];
        });

        return new Collection($result->values());
    }

    public function transporterWise(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getDashboardFiscalYearPeriod();
        if (! $fiscalYearId) {
            return new Collection;
        }

        return $this->stripDetailRows(
            $this->freightService->transporterItemWiseReport($startDate, $endDate)
        );
    }

    public function userWise(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getDashboardFiscalYearPeriod();

        $scope = function ($query) use ($fiscalYearId, $startDate, $endDate) {
            $query->when($fiscalYearId, fn ($q) => $q->where('v.fiscal_year_id', $fiscalYearId))
                ->when($startDate, fn ($q) => $q->whereBetween('v.voucher_date', [$startDate, $endDate]));
        };

        // Voucher counts per user (no amount subquery).
        $counts = DB::table('vouchers as v')
            ->leftJoin('users as u', 'v.created_by', '=', 'u.id')
            ->whereNotNull('v.created_by')
            ->where($scope)
            ->select(
                'u.id as user_id',
                'u.name as user_name',
                DB::raw('COUNT(v.id) as total_vouchers'),
                DB::raw("SUM(CASE WHEN v.voucher_type_id = {$this->deliveryNoteVoucherTypeId} THEN 1 ELSE 0 END) as delivery_notes"),
                DB::raw("SUM(CASE WHEN v.voucher_type_id = {$this->receiptNoteVoucherTypeId} THEN 1 ELSE 0 END) as receipt_notes"),
                DB::raw("SUM(CASE WHEN v.module = 'freight' THEN 1 ELSE 0 END) as freights")
            )
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('total_vouchers')
            ->get();

        // Credit-side totals per user — a join+group is ~150x faster than a
        // correlated subquery evaluated once per voucher row.
        $amounts = DB::table('vouchers as v')
            ->join('voucher_entries as ve', 've.voucher_id', '=', 'v.id')
            ->whereNotNull('v.created_by')
            ->where($scope)
            ->select(
                'v.created_by as user_id',
                DB::raw('COALESCE(SUM(ve.credit), 0) as total_amount')
            )
            ->groupBy('v.created_by')
            ->pluck('total_amount', 'user_id');

        $result = $counts->map(function ($row) use ($amounts) {
            $data = (array) $row;
            $data['total_amount'] = round((float) ($amounts[(string) $row->user_id] ?? 0), 2);

            return $data;
        });

        return new Collection($result->values());
    }

    /**
     * Drop per-entry detail arrays (voucherDetails / godownDetails / entries)
     * from the freight report payloads — the dashboard only needs aggregates.
     */
    protected function stripDetailRows(Collection $rows): Collection
    {
        $items = $rows->map(function ($row) {
            $item = is_array($row) ? $row : (array) $row;
            unset($item['voucherDetails'], $item['godownDetails'], $item['entries']);

            return $item;
        })->all();

        return new Collection($items);
    }

    /**
     * Resolve the user's active fiscal-year period for the DASHBOARD.
     *
     * Unlike report pages (which honor the user's reporting-period window stored
     * in user_fiscal_years.start_date/end_date and editable via the global
     * Alt+P "Period" dialog), the dashboard always shows the ENTIRE active
     * fiscal year: its header is labelled "Fiscal Year 2026-27" and the stat
     * totals are fiscal-year figures, so a narrowed report window must never
     * silently hide dashboard data.
     *
     * @return array{0: ?int, 1: ?string, 2: ?string} [fiscal_year_id, fy_start_date, fy_end_date]
     */
    protected function getDashboardFiscalYearPeriod(): array
    {
        $userFiscalYear = auth()->guard()->user()?->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            return [null, null, null];
        }

        $fiscalYear = $userFiscalYear->fiscal_year;

        return [
            (int) ($fiscalYear?->id ?? $userFiscalYear->fiscal_year_id),
            $fiscalYear?->start_date?->toDateString() ?? $userFiscalYear->start_date?->toDateString(),
            $fiscalYear?->end_date?->toDateString() ?? $userFiscalYear->end_date?->toDateString(),
        ];
    }

    /**
     * Month-by-month activity for the dashboard trend chart (whole fiscal year).
     *
     * @return array<int, array<string, mixed>>
     */
    public function monthlyTrend(): array
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getDashboardFiscalYearPeriod();
        if (! $fiscalYearId) {
            return [];
        }

        // SQLite (tests) and MySQL/MariaDB expose different month functions.
        $monthExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', vouchers.voucher_date)"
            : "DATE_FORMAT(vouchers.voucher_date, '%Y-%m')";

        $byMonth = [];

        // Delivery note / receipt note volumes per month
        $noteRows = Voucher::query()
            ->select(
                'vouchers.voucher_type_id',
                'vouchers.stock_journal_id',
                DB::raw("{$monthExpr} as voucher_month")
            )
            ->whereIn('vouchers.voucher_type_id', [$this->deliveryNoteVoucherTypeId, $this->receiptNoteVoucherTypeId])
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
            ->get();

        foreach ($noteRows as $row) {
            $month = $row->voucher_month;
            $bucket = $byMonth[$month] ?? $this->emptyMonthlyBucket($month);

            if ((int) $row->voucher_type_id === $this->deliveryNoteVoucherTypeId && $row->stock_journal_id !== null) {
                $bucket['deliveryNoteCount']++;
            } elseif ((int) $row->voucher_type_id === $this->receiptNoteVoucherTypeId) {
                $bucket['receiptNoteCount']++;
            }

            $byMonth[$month] = $bucket;
        }

        // Freight (sales) bills + billed amount per month
        $freightRows = Voucher::query()
            ->join('voucher_entries', 'voucher_entries.voucher_id', '=', 'vouchers.id')
            ->select(
                'vouchers.id as voucher_id',
                'voucher_entries.credit',
                DB::raw("{$monthExpr} as voucher_month")
            )
            ->where('vouchers.module', 'freight')
            ->where('vouchers.voucher_type_id', $this->salesVoucherTypeId)
            ->where('vouchers.fiscal_year_id', $fiscalYearId)
            ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
            ->get();

        $seenFreight = [];
        foreach ($freightRows as $row) {
            $month = $row->voucher_month;
            $bucket = $byMonth[$month] ?? $this->emptyMonthlyBucket($month);
            $bucket['freightAmount'] += (float) ($row->credit ?? 0);

            if (! isset($seenFreight[$month][(int) $row->voucher_id])) {
                $seenFreight[$month][(int) $row->voucher_id] = true;
                $bucket['freightCount']++;
            }

            $byMonth[$month] = $bucket;
        }

        // Freight payments collected per month
        $paymentVoucherIds = DB::table('voucher_references')
            ->where('type', 'freight_payment')
            ->pluck('voucher_id');

        if ($paymentVoucherIds->isNotEmpty()) {
            $paymentRows = Voucher::query()
                ->join('voucher_entries', 'voucher_entries.voucher_id', '=', 'vouchers.id')
                ->select(
                    'vouchers.id as voucher_id',
                    'voucher_entries.credit',
                    DB::raw("{$monthExpr} as voucher_month")
                )
                ->whereIn('vouchers.id', $paymentVoucherIds)
                ->where('vouchers.fiscal_year_id', $fiscalYearId)
                ->whereBetween('vouchers.voucher_date', [$startDate, $endDate])
                ->get();

            $seenPayments = [];
            foreach ($paymentRows as $row) {
                $month = $row->voucher_month;
                $bucket = $byMonth[$month] ?? $this->emptyMonthlyBucket($month);
                $bucket['paymentAmount'] += (float) ($row->credit ?? 0);

                if (! isset($seenPayments[$month][(int) $row->voucher_id])) {
                    $seenPayments[$month][(int) $row->voucher_id] = true;
                    $bucket['paymentCount']++;
                }

                $byMonth[$month] = $bucket;
            }
        }

        ksort($byMonth);

        return array_values($byMonth);
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyMonthlyBucket(string $month): array
    {
        return [
            'month' => $month,
            'deliveryNoteCount' => 0,
            'receiptNoteCount' => 0,
            'freightCount' => 0,
            'freightAmount' => 0.0,
            'paymentCount' => 0,
            'paymentAmount' => 0.0,
        ];
    }
}
