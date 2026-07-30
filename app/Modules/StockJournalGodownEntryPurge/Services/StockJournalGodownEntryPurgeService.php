<?php

namespace Modules\StockJournalGodownEntryPurge\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalGodownEntryPurge\Contracts\StockJournalGodownEntryPurgeServiceInterface;
use Modules\StockJournalGodownEntryPurge\Models\StockJournalGodownEntryPurge;

class StockJournalGodownEntryPurgeService extends BaseService implements StockJournalGodownEntryPurgeServiceInterface
{
    protected string $modelClass = StockJournalGodownEntryPurge::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockJournalGodownEntryPurge
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockJournalGodownEntryPurge
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockJournalGodownEntryPurge
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
