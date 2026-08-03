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
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

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
        [$fiscalYearId] = $this->getUserFiscalYearPeriod();
        if (! $fiscalYearId) {
            return new Collection;
        }

        return $this->freightService->zoneWiseReport();
    }

    public function godownWise(): Collection
    {
        [$fiscalYearId] = $this->getUserFiscalYearPeriod();
        if (! $fiscalYearId) {
            return new Collection;
        }

        return $this->freightService->godownWiseReport();
    }

    public function transporterWise(): Collection
    {
        [$fiscalYearId] = $this->getUserFiscalYearPeriod();
        if (! $fiscalYearId) {
            return new Collection;
        }

        return $this->freightService->transporterItemWiseReport();
    }

    public function userWise(): Collection
    {
        [$fiscalYearId, $startDate, $endDate] = $this->getUserFiscalYearPeriod();

        $rows = DB::table('vouchers as v')
            ->leftJoin('users as u', 'v.created_by', '=', 'u.id')
            ->when($fiscalYearId, fn ($q) => $q->where('v.fiscal_year_id', $fiscalYearId))
            ->when($startDate, fn ($q) => $q->whereBetween('v.voucher_date', [$startDate, $endDate]))
            ->whereNotNull('v.created_by')
            ->select(
                'u.id as user_id',
                'u.name as user_name',
                DB::raw('COUNT(v.id) as total_vouchers'),
                DB::raw("SUM(CASE WHEN v.voucher_type_id = {$this->deliveryNoteVoucherTypeId} THEN 1 ELSE 0 END) as delivery_notes"),
                DB::raw("SUM(CASE WHEN v.voucher_type_id = {$this->receiptNoteVoucherTypeId} THEN 1 ELSE 0 END) as receipt_notes"),
                DB::raw("SUM(CASE WHEN v.module = 'freight' THEN 1 ELSE 0 END) as freights"),
                DB::raw('SUM(COALESCE((SELECT SUM(COALESCE(ve.credit, 0)) FROM voucher_entries ve WHERE ve.voucher_id = v.id), 0)) as total_amount')
            )
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('total_vouchers')
            ->get();

        return new Collection($rows->map(fn ($row) => (array) $row)->values());
    }

    /**
     * Resolve the current user's fiscal year period (mirrors FreightService).
     *
     * @return array{0: ?int, 1: ?string, 2: ?string} [fiscal_year_id, start_date, end_date]
     */
    protected function getUserFiscalYearPeriod(): array
    {
        $userFiscalYear = auth()->user()?->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            return [null, null, null];
        }

        return [
            (int) $userFiscalYear->fiscal_year_id,
            $userFiscalYear->start_date,
            $userFiscalYear->end_date,
        ];
    }
}
