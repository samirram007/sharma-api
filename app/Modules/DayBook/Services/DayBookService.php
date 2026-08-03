<?php

namespace Modules\DayBook\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\DayBook\Contracts\DayBookServiceInterface;
use Modules\DayBook\Facades\DayBookRepositoryFacade;
use Modules\DayBook\Models\DayBook;
use Modules\Voucher\Contracts\VoucherServiceInterface;
use Modules\Voucher\Models\Voucher;
use Modules\VoucherType\Models\VoucherType;

class DayBookService extends BaseService implements DayBookServiceInterface
{
    protected string $modelClass = DayBook::class;

    protected array $defaultResource = [
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

    public function __construct(protected VoucherServiceInterface $voucherService) {}

    public function getAll(): LengthAwarePaginator
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }
        $startDate = $userFiscalYear->start_date;
        $endDate = $userFiscalYear->end_date;

        $query = Voucher::with($this->defaultResource)
            ->where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$startDate, $endDate]);

        // Search filter
        $search = request()->input('search');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('voucher_entries.account_ledger', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Voucher type filter (accepts comma-separated IDs or array)
        $voucherTypeId = request()->input('voucher_type_id');
        if (! empty($voucherTypeId)) {
            $voucherTypeIds = is_array($voucherTypeId)
                ? $voucherTypeId
                : explode(',', $voucherTypeId);
            $query->whereIn('voucher_type_id', $voucherTypeIds);
        }

        // Billing preference filter
        $billingPreference = request()->input('billing_preference');
        if (! empty($billingPreference)) {
            $preferences = is_array($billingPreference)
                ? $billingPreference
                : explode(',', $billingPreference);
            $query->whereHas('voucher_dispatch_detail', function ($q) use ($preferences) {
                $q->whereIn('billing_preference', $preferences);
            });
        }

        // Status filter
        $statusParam = request()->input('status');
        if (! empty($statusParam)) {
            $statuses = is_array($statusParam)
                ? $statusParam
                : explode(',', $statusParam);

            $query->where(function ($q) use ($statuses) {
                // Subquery: total paid amount via payment references
                $paidAmountSql = '(SELECT COALESCE(SUM(COALESCE(pv.amount, 0)), 0)
                    FROM voucher_references vr
                    JOIN vouchers pv ON pv.id = vr.voucher_id
                    WHERE vr.ref_voucher_id = vouchers.id
                    AND vr.type IN (\'payment\', \'freight_payment\'))';

                // Subquery: total voucher amount from entries
                $totalAmountSql = '(SELECT COALESCE(SUM(COALESCE(ve.debit, 0) + COALESCE(ve.credit, 0)), 0)
                    FROM voucher_entries ve
                    WHERE ve.voucher_id = vouchers.id)';

                if (in_array('paid', $statuses)) {
                    $q->orWhereRaw("{$paidAmountSql} >= {$totalAmountSql}");
                }
                if (in_array('partially_paid', $statuses)) {
                    $q->orWhereRaw("{$paidAmountSql} > 0 AND {$paidAmountSql} < {$totalAmountSql}");
                }
                if (in_array('unpaid', $statuses)) {
                    $q->orWhereRaw("{$paidAmountSql} = 0");
                }
                if (in_array('freight_done', $statuses)) {
                    $q->orWhere(function ($sq) {
                        $sq->where('voucher_type_id', 2001)
                            ->whereHas('referenced_by', fn ($r) => $r->where('type', 'freight'));
                    });
                }
                if (in_array('no_freight', $statuses)) {
                    $q->orWhere(function ($sq) {
                        $sq->where('voucher_type_id', 2001)
                            ->whereDoesntHave('referenced_by', fn ($r) => $r->where('type', 'freight'));
                    });
                }
            });
        }

        // Sorting
        if (! empty($params['sort_by'])) {
            $sortOrder = ! empty($params['sort_order']) && strtolower($params['sort_order']) === 'desc' ? 'desc' : 'asc';

            match ($params['sort_by']) {
                'billing_preference' => $query->leftJoin('voucher_dispatch_details AS vdd_sort', 'vouchers.id', '=', 'vdd_sort.voucher_id')
                    ->reorder()
                    ->orderBy('vdd_sort.billing_preference', $sortOrder)
                    ->select('vouchers.*'),
                default => null,
            };
        }

        $perPage = request()->integer('per_page', 15) ?? 10;

        $vouchers = $query->paginate($perPage);

        // Transform each voucher with ledger info using through() to keep paginator
        $vouchers->through(fn (Voucher $voucher) => $this->voucherService->attachLedgerInfo($voucher));

        return $vouchers;
    }

    public function dayBooksSelf(array $params = []): LengthAwarePaginator
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
            throw new \Exception('UserFiscalYear not set for the user.');
        }
        $startDate = $userFiscalYear->start_date;
        $endDate = $userFiscalYear->end_date;

        $query = Voucher::with($this->defaultResource)
            ->where('fiscal_year_id', $userFiscalYear->fiscal_year_id)
            ->whereBetween('voucher_date', [$startDate, $endDate])
            ->where('created_by', auth()->id());

        // Search filter
        $search = request()->input('search');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('voucher_entries.account_ledger', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Voucher type filter
        if (! empty($params['voucher_type_id'])) {
            $voucherTypeIds = is_array($params['voucher_type_id'])
                ? $params['voucher_type_id']
                : explode(',', $params['voucher_type_id']);
            $query->whereIn('voucher_type_id', $voucherTypeIds);
        }

        // Billing preference filter
        $billingPreference = request()->input('billing_preference');
        if (! empty($billingPreference)) {
            $preferences = is_array($billingPreference)
                ? $billingPreference
                : explode(',', $billingPreference);
            $query->whereHas('voucher_dispatch_detail', function ($q) use ($preferences) {
                $q->whereIn('billing_preference', $preferences);
            });
        }

        // Status filter
        $statusParam = request()->input('status');
        if (! empty($statusParam)) {
            $statuses = is_array($statusParam)
                ? $statusParam
                : explode(',', $statusParam);

            $query->where(function ($q) use ($statuses) {
                $paidAmountSql = '(SELECT COALESCE(SUM(COALESCE(pv.amount, 0)), 0)
                    FROM voucher_references vr
                    JOIN vouchers pv ON pv.id = vr.voucher_id
                    WHERE vr.ref_voucher_id = vouchers.id
                    AND vr.type IN (\'payment\', \'freight_payment\'))';

                $totalAmountSql = '(SELECT COALESCE(SUM(COALESCE(ve.debit, 0) + COALESCE(ve.credit, 0)), 0)
                    FROM voucher_entries ve
                    WHERE ve.voucher_id = vouchers.id)';

                if (in_array('paid', $statuses)) {
                    $q->orWhereRaw("{$paidAmountSql} >= {$totalAmountSql}");
                }
                if (in_array('partially_paid', $statuses)) {
                    $q->orWhereRaw("{$paidAmountSql} > 0 AND {$paidAmountSql} < {$totalAmountSql}");
                }
                if (in_array('unpaid', $statuses)) {
                    $q->orWhereRaw("{$paidAmountSql} = 0");
                }
                if (in_array('freight_done', $statuses)) {
                    $q->orWhere(function ($sq) {
                        $sq->where('voucher_type_id', 2001)
                            ->whereHas('referenced_by', fn ($r) => $r->where('type', 'freight'));
                    });
                }
                if (in_array('no_freight', $statuses)) {
                    $q->orWhere(function ($sq) {
                        $sq->where('voucher_type_id', 2001)
                            ->whereDoesntHave('referenced_by', fn ($r) => $r->where('type', 'freight'));
                    });
                }
            });
        }

        // Sorting
        if (! empty($params['sort_by'])) {
            $sortOrder = ! empty($params['sort_order']) && strtolower($params['sort_order']) === 'desc' ? 'desc' : 'asc';

            match ($params['sort_by']) {
                'billing_preference' => $query->leftJoin('voucher_dispatch_details AS vdd_sort', 'vouchers.id', '=', 'vdd_sort.voucher_id')
                    ->reorder()
                    ->orderBy('vdd_sort.billing_preference', $sortOrder)
                    ->select('vouchers.*'),
                default => null,
            };
        }

        $perPage = request()->integer('per_page', 15) ?? 10;

        $vouchers = $query->paginate($perPage);

        $vouchers->through(fn (Voucher $voucher) => $this->voucherService->attachLedgerInfo($voucher));

        return $vouchers;
    }

    public function getUsedVoucherTypes(): Collection
    {
        $userFiscalYear = auth()->user()->user_fiscal_year()->first();
        if (! $userFiscalYear) {
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
}
