<?php

namespace Modules\StockJournalBatchEntry\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalBatchEntry\Contracts\StockJournalBatchEntryServiceInterface;
use Modules\StockJournalBatchEntry\Models\StockJournalBatchEntry;

class StockJournalBatchEntryService extends BaseService implements StockJournalBatchEntryServiceInterface
{
    protected string $modelClass = StockJournalBatchEntry::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockJournalBatchEntry
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockJournalBatchEntry
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockJournalBatchEntry
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
