<?php

namespace Modules\StockJournalStorageUnitEntryPurge\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalStorageUnitEntryPurge\Contracts\StockJournalStorageUnitEntryPurgeServiceInterface;
use Modules\StockJournalStorageUnitEntryPurge\Models\StockJournalStorageUnitEntryPurge;

class StockJournalStorageUnitEntryPurgeService extends BaseService implements StockJournalStorageUnitEntryPurgeServiceInterface
{
    protected string $modelClass = StockJournalStorageUnitEntryPurge::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockJournalStorageUnitEntryPurge
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockJournalStorageUnitEntryPurge
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockJournalStorageUnitEntryPurge
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
