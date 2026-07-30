<?php

namespace Modules\StockJournalStorageUnitEntry\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalStorageUnitEntry\Contracts\StockJournalStorageUnitEntryServiceInterface;
use Modules\StockJournalStorageUnitEntry\Models\StockJournalStorageUnitEntry;

class StockJournalStorageUnitEntryService extends BaseService implements StockJournalStorageUnitEntryServiceInterface
{
    protected string $modelClass = StockJournalStorageUnitEntry::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockJournalStorageUnitEntry
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockJournalStorageUnitEntry
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockJournalStorageUnitEntry
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
