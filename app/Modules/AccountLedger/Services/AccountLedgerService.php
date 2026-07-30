<?php

namespace Modules\AccountLedger\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\AccountLedger\Contracts\AccountLedgerServiceInterface;
use Modules\AccountLedger\Models\AccountLedger;
use Modules\UserFiscalYear\Contracts\UserFiscalYearServiceInterface;

class AccountLedgerService extends BaseService implements AccountLedgerServiceInterface
{
    protected string $modelClass = AccountLedger::class;

    protected array $defaultResource = [
        'account_group.account_nature',
    ];

    protected array $ledgerableResource = [
        'account_group.account_nature',
        'ledgerable.address.state',
        'ledgerable.address.country',
    ];

    public function __construct(
        protected UserFiscalYearServiceInterface $userFiscalYearService
    ) {}

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): AccountLedger
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): AccountLedger
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): AccountLedger
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }

    public function getLedgerBalance(int $id): ?array
    {
        $ledger = AccountLedger::with('account_nature')->find($id);

        if (! $ledger) {
            return null;
        }

        $balance = $this->calculateLedgerBalance($ledger);
        $nature = strtolower($ledger->account_nature->accounting_effect);

        $drCr = $nature === 'debit' ? 'DR' : 'CR';

        if ($balance < 0) {
            $balance = abs($balance);
            $drCr = $drCr === 'DR' ? 'CR' : 'DR';
        }

        return [
            'id' => $ledger->id,
            'balance' => $balance,
            'nature' => $drCr,
        ];
    }

    private function calculateLedgerBalance(AccountLedger $ledger): float
    {
        $user = auth()->user();

        if (! $user) {
            return 0.0;
        }

        $userFiscalYear = $this->userFiscalYearService->getByUserId($user->id);

        if (! $userFiscalYear) {
            return 0.0;
        }

        $totals = $ledger->voucher_entries()
            ->whereHas('voucher', function ($q) use ($userFiscalYear) {
                $q->where('fiscal_year_id', $userFiscalYear->fiscal_year_id);
            })
            ->selectRaw('SUM(debit) as debitTotal, SUM(credit) as creditTotal')
            ->first();

        return ($totals->debitTotal ?? 0) - ($totals->creditTotal ?? 0);
    }

    public function getPurchaseLedgers(): Collection
    {
        return AccountLedger::with($this->defaultResource)
            ->where('account_group_id', 40004)->orderBy('name')->get();
    }

    public function getSaleLedgers(): Collection
    {
        return AccountLedger::with($this->defaultResource)
            ->where('account_group_id', 30004)->orderBy('name')->get();
    }

    public function getSupplierLedgers(): Collection
    {
        return AccountLedger::with($this->ledgerableResource)
            ->where('account_group_id', 20003)->orderBy('name')->get();
    }

    public function getDistributorLedgers(): Collection
    {
        return AccountLedger::with($this->ledgerableResource)
            ->where('account_group_id', 10008)->orderBy('name')->get();
    }

    public function getStockInHandLedgers(): Collection
    {
        return AccountLedger::with($this->defaultResource)
            ->where('account_group_id', 10009)->orderBy('name')->get();
    }
}
