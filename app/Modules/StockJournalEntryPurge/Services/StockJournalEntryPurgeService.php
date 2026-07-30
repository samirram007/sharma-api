<?php

namespace Modules\StockJournalEntryPurge\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalEntryPurge\Contracts\StockJournalEntryPurgeServiceInterface;
use Modules\StockJournalEntryPurge\Models\StockJournalEntryPurge;

class StockJournalEntryPurgeService extends BaseService implements StockJournalEntryPurgeServiceInterface
{
    protected string $modelClass = StockJournalEntryPurge::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockJournalEntryPurge
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockJournalEntryPurge
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockJournalEntryPurge
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
