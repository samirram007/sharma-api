<?php

namespace App\Modules\DayBook\Services;

use App\Modules\AccountLedger\Models\AccountLedger;
use App\Modules\DayBook\Contracts\DayBookServiceInterface;
use App\Modules\DayBook\Models\DayBook;
use App\Modules\Voucher\Contracts\VoucherServiceInterface;
use App\Modules\Voucher\Models\Voucher;
use App\Modules\VoucherEntry\Models\VoucherEntry;
use App\Modules\VoucherType\Models\VoucherType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DayBookService implements DayBookServiceInterface
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
        $startDate = $userFiscalYear->start_date;
        $endDate = $userFiscalYear->end_date;

        $query = Voucher::with($this->resource)
            ->where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$startDate, $endDate]);

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

        // Voucher type filter (accepts comma-separated IDs or array)
        if (!empty($params['voucher_type_id'])) {
            $voucherTypeIds = is_array($params['voucher_type_id'])
                ? $params['voucher_type_id']
                : explode(',', $params['voucher_type_id']);
            $query->whereIn('voucher_type_id', $voucherTypeIds);
        }

        $perPage = $params['per_page'] ?? 10;

        $vouchers = $query->paginate($perPage);

        // Transform each voucher with ledger info using through() to keep paginator
        $vouchers->through(fn(Voucher $voucher) => $this->voucherService->attachLedgerInfo($voucher));

        return $vouchers;
    }

    public function dayBooksSelf(array $params = []): LengthAwarePaginator
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }
        $startDate = $userFiscalYear->start_date;
        $endDate = $userFiscalYear->end_date;

        $query = Voucher::with($this->resource)
            ->where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$startDate, $endDate])
            ->where('created_by', auth()->id());

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

        // Voucher type filter
        if (!empty($params['voucher_type_id'])) {
            $voucherTypeIds = is_array($params['voucher_type_id'])
                ? $params['voucher_type_id']
                : explode(',', $params['voucher_type_id']);
            $query->whereIn('voucher_type_id', $voucherTypeIds);
        }

        $perPage = $params['per_page'] ?? 10;

        $vouchers = $query->paginate($perPage);

        $vouchers->through(fn(Voucher $voucher) => $this->voucherService->attachLedgerInfo($voucher));

        return $vouchers;
    }

    public function getUsedVoucherTypes(): Collection
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (!$userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }

        $usedTypeIds = Voucher::where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$userFiscalYear->start_date, $userFiscalYear->end_date])
            ->whereNotNull('voucher_type_id')
            ->distinct()
            ->pluck('voucher_type_id');

        return VoucherType::whereIn('id', $usedTypeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function getById(int $id): ?DayBook
    {
        return DayBook::with($this->resource)->findOrFail($id);
    }

    public function store(array $data): DayBook
    {
        return DayBook::create($data);
    }

    public function update(array $data, int $id): DayBook
    {
        $record = DayBook::findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = DayBook::findOrFail($id);
        return $record->delete();
    }
}
