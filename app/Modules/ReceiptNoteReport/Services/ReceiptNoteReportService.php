<?php

namespace App\Modules\ReceiptNoteReport\Services;

use App\Modules\Voucher\Contracts\VoucherServiceInterface;
use App\Modules\Voucher\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReceiptNoteReportService
{
    protected $resource = [
        'voucher_type',
        'voucher_entries.account_ledger',
        'stock_journal.stock_journal_entries.rate_unit',
        'stock_journal.stock_journal_entries.stock_item.stock_unit',
        'stock_journal.stock_journal_entries.stock_item.alternate_stock_unit',
        'stock_journal.stock_journal_entries.alternate_unit',
        'stock_journal.stock_journal_entries.stock_journal_godown_entries.godown',
        'voucher_party.state',
        'voucher_party.country',
        'voucher_dispatch_detail',
        'referenced_by',
        'company',
        'fiscal_year',
    ];

    public function __construct(protected VoucherServiceInterface $voucherService)
    {
    }

    public function getAll(array $params = []): LengthAwarePaginator
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = Voucher::with($this->resource)
            ->where('voucher_type_id', 2002)
            ->where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date]);

        // Search filter
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('voucher_entries.account_ledger', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        if (!empty($params['sort_by'])) {
            $sortOrder = !empty($params['sort_order']) && strtolower($params['sort_order']) === 'desc' ? 'desc' : 'asc';
            match ($params['sort_by']) {
                'voucher_date' => $query->reorder()->orderBy('voucher_date', $sortOrder),
                'voucher_no' => $query->reorder()->orderBy('voucher_no', $sortOrder),
                'amount' => $query->reorder()->orderBy(
                    DB::raw('(SELECT COALESCE(SUM(COALESCE(ve.debit, 0) + COALESCE(ve.credit, 0)), 0) FROM voucher_entries ve WHERE ve.voucher_id = vouchers.id)'),
                    $sortOrder
                ),
                default => null,
            };
        }

        $perPage = $params['per_page'] ?? 10;
        $vouchers = $query->paginate($perPage);
        $vouchers->through(fn(Voucher $voucher) => $this->voucherService->attachLedgerInfo($voucher));

        return $vouchers;
    }

    public function getGroupedByLedger(array $params = []): Collection
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = Voucher::where('vouchers.voucher_type_id', 2002)
            ->where('vouchers.fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('vouchers.voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->join('voucher_entries', 'vouchers.id', '=', 'voucher_entries.voucher_id')
            ->join('account_ledgers', 'voucher_entries.account_ledger_id', '=', 'account_ledgers.id')
            ->select(
                'account_ledgers.id as ledger_id',
                'account_ledgers.name as ledger_name',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw('SUM(voucher_entries.debit) as total_debit'),
                DB::raw('SUM(voucher_entries.credit) as total_credit'),
                DB::raw('SUM(COALESCE(voucher_entries.debit, 0) + COALESCE(voucher_entries.credit, 0)) as total_amount')
            )
            ->groupBy('account_ledgers.id', 'account_ledgers.name')
            ->orderBy('total_amount', 'desc');

        // Date range filter
        if (!empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }

    public function getGroupedByDate(array $params = []): Collection
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = Voucher::where('vouchers.voucher_type_id', 2002)
            ->where('vouchers.fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('vouchers.voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->join('voucher_entries', 'vouchers.id', '=', 'voucher_entries.voucher_id')
            ->select(
                'vouchers.voucher_date',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw('SUM(COALESCE(voucher_entries.debit, 0) + COALESCE(voucher_entries.credit, 0)) as total_amount')
            )
            ->groupBy('vouchers.voucher_date')
            ->orderBy('vouchers.voucher_date', 'desc');

        if (!empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }

    public function getGroupedByStockItem(array $params = []): Collection
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = DB::table('vouchers')
            ->where('vouchers.voucher_type_id', 2002)
            ->where('vouchers.fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('vouchers.voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->join('stock_journals', 'vouchers.stock_journal_id', '=', 'stock_journals.id')
            ->join('stock_journal_entries', 'stock_journals.id', '=', 'stock_journal_entries.stock_journal_id')
            ->join('stock_items', 'stock_journal_entries.stock_item_id', '=', 'stock_items.id')
            ->select(
                'stock_items.id as stock_item_id',
                'stock_items.name as stock_item_name',
                DB::raw('COUNT(DISTINCT vouchers.id) as voucher_count'),
                DB::raw('SUM(stock_journal_entries.actual_quantity) as total_quantity'),
                DB::raw('SUM(stock_journal_entries.amount) as total_amount')
            )
            ->groupBy('stock_items.id', 'stock_items.name')
            ->orderBy('total_amount', 'desc');

        if (!empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }

    public function getGroupedByGodown(array $params = []): Collection
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $query = DB::table('vouchers')
            ->where('vouchers.voucher_type_id', 2002)
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
                DB::raw('SUM(stock_journal_godown_entries.actual_quantity) as total_quantity'),
                DB::raw('SUM(stock_journal_godown_entries.billing_quantity) as total_billing_quantity')
            )
            ->groupBy('godowns.id', 'godowns.name')
            ->orderBy('godowns.name');

        if (!empty($params['from_date'])) {
            $query->whereDate('vouchers.voucher_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $query->whereDate('vouchers.voucher_date', '<=', $params['to_date']);
        }

        return $query->get();
    }
}
