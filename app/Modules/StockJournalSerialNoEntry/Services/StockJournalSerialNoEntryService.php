<?php

namespace Modules\StockJournalSerialNoEntry\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\StockJournalSerialNoEntry\Contracts\StockJournalSerialNoEntryServiceInterface;
use Modules\StockJournalSerialNoEntry\Models\StockJournalSerialNoEntry;

class StockJournalSerialNoEntryService extends BaseService implements StockJournalSerialNoEntryServiceInterface
{
    protected string $modelClass = StockJournalSerialNoEntry::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?StockJournalSerialNoEntry
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): StockJournalSerialNoEntry
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): StockJournalSerialNoEntry
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
