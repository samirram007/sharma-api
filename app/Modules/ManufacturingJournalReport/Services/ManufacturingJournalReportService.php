<?php

namespace Modules\ManufacturingJournalReport\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\ManufacturingJournalReport\Contracts\ManufacturingJournalReportServiceInterface;
use Modules\Voucher\Models\Voucher;

class ManufacturingJournalReportService extends BaseService implements ManufacturingJournalReportServiceInterface
{
    protected string $modelClass = Voucher::class;

    protected array $defaultResource = [
        'voucher_type',
        'voucher_entries.account_ledger',
        'stock_journal.stock_journal_entries.rate_unit',
        'stock_journal.stock_journal_entries.stock_item.stock_unit',
        'stock_journal.stock_journal_entries.stock_item.alternate_stock_unit',
        'stock_journal.stock_journal_entries.alternate_unit',
        'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
        'referenced_by',
        'company',
        'fiscal_year',
    ];

    public function getAll(): LengthAwarePaginator
    {
        $userFiscalYear = auth()->guard()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = Voucher::with($this->defaultResource)
            ->where('voucher_type_id', 2006)
            ->where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date]);

        // Search filter
        $search = request()->input('search');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('stock_journal.stock_journal_entries.stock_item', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = request()->input('sort_by');
        if (! empty($sortBy)) {
            $sortOrder = request()->input('sort_order', 'asc');
            $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';
            match ($sortBy) {
                'voucher_date' => $query->reorder()->orderBy('voucher_date', $sortOrder),
                'voucher_no' => $query->reorder()->orderBy('voucher_no', $sortOrder),
                'amount' => $query->reorder()->orderBy(
                    DB::raw('(SELECT COALESCE(SUM(COALESCE(ve.debit, 0) + COALESCE(ve.credit, 0)), 0) FROM voucher_entries ve WHERE ve.voucher_id = vouchers.id)'),
                    $sortOrder
                ),
                default => null,
            };
        }

        $perPage = request()->integer('per_page', 15);
        $vouchers = $query->paginate($perPage);
        $vouchers->through(fn (Voucher $voucher) => $this->attachConsumptionProduction($voucher));

        return $vouchers;
    }

    /**
     * Attach consumption (OUT) / production (IN) quantities computed from stock journal entries.
     */
    protected function attachConsumptionProduction(Voucher $voucher): Voucher
    {
        $consumptionQty = 0;
        $productionQty = 0;
        $consumptionAmount = 0;
        $productionAmount = 0;

        foreach ($voucher->stock_journal->stock_journal_entries ?? [] as $entry) {
            if (strtolower((string) $entry->movement_type) === 'in') {
                $productionQty += (float) $entry->actual_quantity;
                $productionAmount += (float) $entry->amount;
            } else {
                $consumptionQty += (float) $entry->actual_quantity;
                $consumptionAmount += (float) $entry->amount;
            }
        }

        $voucher->consumption_qty = $consumptionQty;
        $voucher->production_qty = $productionQty;
        $voucher->consumption_amount = $consumptionAmount;
        $voucher->production_amount = $productionAmount;

        return $voucher;
    }

    public function getGroupedByStockItem(array $params = []): Collection
    {
        $userFiscalYear = auth()->guard()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = DB::table('vouchers')
            ->where('vouchers.voucher_type_id', 2006)
            ->where('vouchers.fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('vouchers.voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->join('stock_journals', 'vouchers.stock_journal_id', '=', 'stock_journals.id')
            ->join('stock_journal_entries', 'stock_journals.id', '=', 'stock_journal_entries.stock_journal_id')
            ->join('stock_items', 'stock_journal_entries.stock_item_id', '=', 'stock_items.id')
            ->select(
                'stock_items.id as stock_item_id',
                'stock_items.name as stock_item_name',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'out' THEN stock_journal_entries.actual_quantity ELSE 0 END) as total_out_quantity"),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'in' THEN stock_journal_entries.actual_quantity ELSE 0 END) as total_in_quantity"),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'out' THEN stock_journal_entries.amount ELSE 0 END) as total_out_amount"),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'in' THEN stock_journal_entries.amount ELSE 0 END) as total_in_amount")
            )
            ->groupBy('stock_items.id', 'stock_items.name')
            ->orderByDesc(DB::raw('SUM(stock_journal_entries.amount)'));

        if (! empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (! empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }

    public function getGroupedByGodown(array $params = []): Collection
    {
        $userFiscalYear = auth()->guard()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = DB::table('vouchers')
            ->where('vouchers.voucher_type_id', 2006)
            ->where('vouchers.fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('vouchers.voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->join('stock_journals', 'vouchers.stock_journal_id', '=', 'stock_journals.id')
            ->join('stock_journal_entries', 'stock_journals.id', '=', 'stock_journal_entries.stock_journal_id')
            ->join('stock_journal_godown_entries', 'stock_journal_entries.id', '=', 'stock_journal_godown_entries.stock_journal_entry_id')
            ->join('godowns', 'stock_journal_godown_entries.godown_id', '=', 'godowns.id')
            ->select(
                'godowns.id as godown_id',
                'godowns.name as godown_name',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw("SUM(CASE WHEN stock_journal_godown_entries.movement_type = 'out' THEN stock_journal_godown_entries.actual_quantity ELSE 0 END) as total_out_quantity"),
                DB::raw("SUM(CASE WHEN stock_journal_godown_entries.movement_type = 'in' THEN stock_journal_godown_entries.actual_quantity ELSE 0 END) as total_in_quantity")
            )
            ->groupBy('godowns.id', 'godowns.name')
            ->orderBy('godowns.name');

        if (! empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (! empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }

    public function getGroupedByDate(array $params = []): Collection
    {
        $userFiscalYear = auth()->guard()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = DB::table('vouchers')
            ->where('vouchers.voucher_type_id', 2006)
            ->where('vouchers.fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('vouchers.voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->join('stock_journals', 'vouchers.stock_journal_id', '=', 'stock_journals.id')
            ->join('stock_journal_entries', 'stock_journals.id', '=', 'stock_journal_entries.stock_journal_id')
            ->select(
                'vouchers.voucher_date',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'out' THEN stock_journal_entries.actual_quantity ELSE 0 END) as total_out_quantity"),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'in' THEN stock_journal_entries.actual_quantity ELSE 0 END) as total_in_quantity"),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'out' THEN stock_journal_entries.amount ELSE 0 END) as total_out_amount"),
                DB::raw("SUM(CASE WHEN stock_journal_entries.movement_type = 'in' THEN stock_journal_entries.amount ELSE 0 END) as total_in_amount")
            )
            ->groupBy('vouchers.voucher_date')
            ->orderBy('vouchers.voucher_date', 'desc');

        if (! empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (! empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }
}
