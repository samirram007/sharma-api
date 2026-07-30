<?php

namespace Modules\OrderJournal\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Modules\OrderJournal\Contracts\OrderJournalServiceInterface;
use Modules\OrderJournal\Models\OrderJournal;

class OrderJournalService extends BaseService implements OrderJournalServiceInterface
{
    protected string $modelClass = OrderJournal::class;

    public function getAll(): Collection
    {
        return $this->getAllRecords();
    }

    public function getById(int $id): ?OrderJournal
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): OrderJournal
    {
        return $this->createRecord($data);
    }

    public function update(array $data, int $id): OrderJournal
    {
        return $this->updateRecord($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord($id);
    }
}
