<?php

namespace Modules\DistributorBook\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\DistributorBook\Contracts\DistributorBookServiceInterface;
use Modules\DistributorBook\Models\DistributorBook;

class DistributorBookService extends BaseService implements DistributorBookServiceInterface
{
    protected string $modelClass = DistributorBook::class;

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
        'company',
        'fiscal_year',
        'voucher_references',
        'referenced_by',
    ];

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?DistributorBook
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): DistributorBook
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): DistributorBook
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
